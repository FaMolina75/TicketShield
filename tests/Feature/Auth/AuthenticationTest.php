<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Http;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
        ]),
    ]);
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'g-recaptcha-response' => 'valid-captcha-token',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('auth_audit_logs', [
        'user_id' => $user->id,
        'email' => $user->email,
        'event' => 'account_logged_in',
    ], 'auth_logs');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-captcha-token';
    });
});

test('users can not authenticate with invalid password', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
        ]),
    ]);
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'g-recaptcha-response' => 'valid-captcha-token',
    ]);

    $this->assertGuest();
});

test('users can not authenticate when reCAPTCHA verification fails', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => false,
        ]),
    ]);
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'g-recaptcha-response' => 'invalid-captcha-token',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');
    $this->assertGuest();
});

test('users can not authenticate without a reCAPTCHA response', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);
    Http::fake();
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');
    Http::assertNothingSent();
    $this->assertGuest();
});

test('users can not authenticate when reCAPTCHA is not configured', function () {
    config(['services.recaptcha.secret_key' => null]);
    Http::fake();

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
        'g-recaptcha-response' => 'captcha-token',
    ]);

    $response->assertSessionHasErrors('g-recaptcha-response');
    Http::assertNothingSent();
    $this->assertGuest();
});
