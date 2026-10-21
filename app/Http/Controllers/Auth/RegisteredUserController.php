<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthAuditLog;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Mostrar el formulario de registro.
     */
    public function create()
    {
        return Inertia::render('Auth/Register', [
            'recaptchaSiteKey' => config('services.recaptcha.secret_key')
                ? config('services.recaptcha.site_key')
                : null,
        ]);
    }

    /**
     * Procesar el registro de un nuevo usuario.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos del formulario y exigir CAPTCHA.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
            'g-recaptcha-response' => ['required', 'string'],
        ]);

        // 2. Verificar CAPTCHA en el servidor antes de crear la cuenta.
        $this->verifyRecaptcha($request);

        // 3. Crear el usuario únicamente después de validar CAPTCHA.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 4. Asignar el rol de usuario.
        Role::findOrCreate('user', 'web');
        $user->assignRole('user');

        // 5. Registrar el evento de creación de cuenta.
        try {
            AuthAuditLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => 'account_created',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        // 6. Ejecutar el evento de registro (protegido contra fallos de servidor SMTP).
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
        }

        // 7. Iniciar sesión con la cuenta recién creada.
        Auth::login($user);

        // 8. Redirigir a la página principal.
        return redirect()->route('home');
    }

    /**
     * Verificar el token de Google reCAPTCHA v2.
     */
    protected function verifyRecaptcha(Request $request): void
    {
        $secret = config('services.recaptcha.secret_key');

        // Si falta la clave secreta, no permitir el registro.
        if (!$secret) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' =>
                    'La verificación de seguridad no está configurada.',
            ]);
        }

        try {
            // Enviar el token a Google para verificar su autenticidad.
            $response = Http::asForm()
                ->timeout(5)
                ->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'secret' => $secret,
                        'response' => $request->input(
                            'g-recaptcha-response'
                        ),
                        'remoteip' => $request->ip(),
                    ]
                );
        } catch (ConnectionException $exception) {
            // Si Google no responde, rechazar el registro.
            throw ValidationException::withMessages([
                'g-recaptcha-response' =>
                    'No se pudo verificar reCAPTCHA. Inténtalo de nuevo.',
            ]);
        }

        // Rechazar tokens inválidos o respuestas incorrectas.
        if (
            !$response->successful() ||
            $response->json('success') !== true
        ) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' =>
                    'Verificación reCAPTCHA inválida. Inténtalo de nuevo.',
            ]);
        }
    }
}