<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Utilizar una clave ficticia únicamente durante las pruebas.
    config()->set('services.recaptcha.site_key', 'test-site-key');
    config()->set('services.recaptcha.secret_key', 'test-secret-key');
});

/**
 * PRUEBA 1:
 * La página de registro debe abrir correctamente.
 */
test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

/**
 * PRUEBA 2:
 * Un registro sin CAPTCHA debe ser rechazado.
 */
test('registration without captcha is rejected', function () {
    $email = 'sin-captcha-' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Usuario Prueba',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');

    $this->assertDatabaseMissing('users', [
        'email' => $email,
    ]);

    $this->assertGuest();
});

/**
 * PRUEBA 3:
 * Un CAPTCHA inválido debe ser rechazado.
 */
test('registration with invalid captcha is rejected', function () {
    Http::fake([
        'www.google.com/recaptcha/api/siteverify' =>
            Http::response(['success' => false], 200),
    ]);

    $email = 'captcha-invalido-' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Usuario Prueba',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'g-recaptcha-response' => 'token-invalido',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');

    $this->assertDatabaseMissing('users', [
        'email' => $email,
    ]);

    $this->assertGuest();
});

/**
 * PRUEBA 4:
 * Si Google reCAPTCHA no responde correctamente,
 * el registro debe ser rechazado.
 */
test('registration is rejected when recaptcha service fails', function () {
    Http::fake([
        'www.google.com/recaptcha/api/siteverify' =>
            Http::response([], 503),
    ]);

    $email = 'captcha-error-' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Usuario Prueba',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'g-recaptcha-response' => 'token-prueba',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');

    $this->assertDatabaseMissing('users', [
        'email' => $email,
    ]);

    $this->assertGuest();
});

/**
 * PRUEBA 5:
 * Un usuario con CAPTCHA válido puede registrarse.
 */
test('new users can register', function () {
    Http::fake([
        'www.google.com/recaptcha/api/siteverify' =>
            Http::response(['success' => true], 200),
    ]);

    $email = 'test-' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'g-recaptcha-response' => 'token-valido',
    ]);

    $response->assertRedirect(route('home'));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => $email,
    ]);

    expect(
        User::findOrFail(auth()->id())->hasRole('user')
    )->toBeTrue();

    $this->assertDatabaseHas('auth_audit_logs', [
        'user_id' => auth()->id(),
        'email' => $email,
        'event' => 'account_created',
    ]);
});

/**
 * PRUEBA 6:
 * Laravel debe limitar las solicitudes repetidas de registro.
 */
test('registration is rate limited after five attempts', function () {
    $data = [
        'name' => 'Usuario Prueba',
        'email' => 'rate-limit-' . uniqid() . '@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    // Cinco solicitudes sin CAPTCHA.
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    // La sexta solicitud debe ser bloqueada.
    $response = $this->post('/register', $data);

    $response->assertStatus(429);

    $this->assertDatabaseMissing('users', [
        'email' => $data['email'],
    ]);
});