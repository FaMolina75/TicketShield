<?php

namespace Tests\Feature\Security;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    /**
     * PRUEBA 1:
     * Rechazar un inicio de sesión sin CAPTCHA.
     */
    public function test_login_without_captcha_is_rejected(): void
    {
        $response = $this->post('/login', [
            'email' => 'bot@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors([
            'g-recaptcha-response',
        ]);

        $this->assertGuest();
    }

    /**
     * PRUEBA 2:
     * Rechazar un CAPTCHA inválido.
     */
    public function test_login_with_invalid_captcha_is_rejected(): void
    {
        // Simulamos que Google rechaza el CAPTCHA.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        // Clave ficticia utilizada únicamente en la prueba.
        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        $response = $this->post('/login', [
            'email' => 'bot@example.com',
            'password' => 'incorrect-password',
            'g-recaptcha-response' => 'captcha-falso',
        ]);

        // El CAPTCHA inválido debe rechazarse.
        $response->assertSessionHasErrors([
            'g-recaptcha-response',
        ]);

        // El usuario no debe quedar autenticado.
        $this->assertGuest();

        // Verificamos que se consultó el servicio simulado.
        Http::assertSentCount(1);
    }

    /**
     * PRUEBA 3:
     * Rechazar el inicio de sesión cuando
     * el servicio de Google reCAPTCHA falla.
     */
    public function test_login_is_rejected_when_recaptcha_service_fails(): void
    {
        // Simulamos un error HTTP 503 de Google.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'error' => 'Service Unavailable',
            ], 503),
        ]);

        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        $response = $this->post('/login', [
            'email' => 'bot@example.com',
            'password' => 'incorrect-password',
            'g-recaptcha-response' => 'token-de-prueba',
        ]);

        // El sistema debe rechazar el inicio de sesión.
        $response->assertSessionHasErrors([
            'g-recaptcha-response',
        ]);

        $this->assertGuest();

        Http::assertSentCount(1);
    }

    /**
     * PRUEBA 4:
     * Bloquear el inicio de sesión después
     * de cinco intentos fallidos.
     */
    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        // Tabla temporal de usuarios.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Simulamos respuestas válidas de Google reCAPTCHA.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        $credentials = [
            'email' => 'bot@example.com',
            'password' => 'incorrect-password',
            'g-recaptcha-response' => 'token-de-prueba',
        ];

        // Cinco intentos fallidos.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post('/login', $credentials);

            $response->assertSessionHasErrors(['email']);

            $this->assertGuest();
        }

        // Escuchamos el evento de bloqueo.
        Event::fake([Lockout::class]);

        // El sexto intento debe ser bloqueado.
        $response = $this->post('/login', $credentials);

        $response->assertSessionHasErrors(['email']);

        // Comprobamos que Laravel emitió el evento Lockout.
        Event::assertDispatched(Lockout::class);

        // Construimos una solicitud para consultar
        // la misma clave de limitación de intentos.
        $loginRequest = LoginRequest::create(
            '/login',
            'POST',
            $credentials
        );

        $loginRequest->server->set('REMOTE_ADDR', '127.0.0.1');

        // Verificamos que el límite de intentos está activo.
        $this->assertTrue(
            RateLimiter::tooManyAttempts(
                $loginRequest->throttleKey(),
                5
            )
        );

        $this->assertGuest();
    }

    /**
     * PRUEBA 5:
     * Rechazar credenciales incorrectas
     * aunque el CAPTCHA sea válido.
     */
    public function test_login_with_valid_captcha_and_wrong_password_is_rejected(): void
    {
        // Tabla temporal de usuarios.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Simulamos un CAPTCHA válido.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        // Intentamos acceder con credenciales incorrectas.
        $response = $this->post('/login', [
            'email' => 'usuario-inexistente@example.com',
            'password' => 'contrasena-incorrecta',
            'g-recaptcha-response' => 'token-simulado',
        ]);

        // Laravel debe rechazar las credenciales.
        $response->assertSessionHasErrors(['email']);

        $this->assertGuest();

        Http::assertSentCount(1);
    }

    /**
     * PRUEBA 6:
     * Rechazar una contraseña incorrecta
     * para un usuario que sí existe.
     */
    public function test_existing_user_with_wrong_password_is_rejected(): void
    {
        // Tabla temporal de usuarios.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        // Creamos un usuario ficticio.
        User::create([
            'name' => 'Usuario de Prueba',
            'email' => 'prueba@example.com',
            'password' => Hash::make('PasswordCorrecta123!'),
        ]);

        // Simulamos un CAPTCHA válido.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        // Contraseña incorrecta para un usuario existente.
        $response = $this->post('/login', [
            'email' => 'prueba@example.com',
            'password' => 'PasswordIncorrecta123!',
            'g-recaptcha-response' => 'token-simulado',
        ]);

        // Laravel debe rechazar el acceso.
        $response->assertSessionHasErrors(['email']);

        $this->assertGuest();

        Http::assertSentCount(1);
    }

    /**
     * PRUEBA 7:
     * Permitir el inicio de sesión con credenciales válidas,
     * CAPTCHA válido y registro de auditoría.
     */
    public function test_user_can_login_with_valid_credentials_and_captcha(): void
    {
        // 1. Creamos una tabla temporal de usuarios.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        // 2. Preparamos las tablas de roles de Spatie.
        // IMPORTANTE: se ejecuta sobre SQLite en memoria
        // cuando utilizamos el comando de pruebas indicado abajo.
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2022_03_22_053638_create_permission_tables.php',
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        // 3. Creamos la tabla temporal de auditoría.
        // Su estructura coincide con las migraciones de TicketShield.
        Schema::create('auth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('event');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 4. Creamos un usuario ficticio.
        $user = User::create([
            'name' => 'Usuario Legítimo',
            'email' => 'legitimo@example.com',
            'password' => Hash::make('PasswordCorrecta123!'),
        ]);

        // 5. Simulamos una respuesta válida de Google reCAPTCHA.
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);

        // 6. Iniciamos sesión con credenciales correctas.
        $response = $this->post('/login', [
            'email' => 'legitimo@example.com',
            'password' => 'PasswordCorrecta123!',
            'g-recaptcha-response' => 'token-valido-simulado',
        ]);

        // 7. Verificamos que el usuario quedó autenticado.
        $this->assertAuthenticatedAs($user);

        // 8. Verificamos que Laravel respondió con una redirección.
        $response->assertRedirect();

        // 9. Confirmamos que se verificó el CAPTCHA.
        Http::assertSentCount(1);

        // 10. Confirmamos que TicketShield registró
        // correctamente el inicio de sesión en la auditoría.
        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'email' => 'legitimo@example.com',
            'event' => 'account_logged_in',
        ]);
    }
}