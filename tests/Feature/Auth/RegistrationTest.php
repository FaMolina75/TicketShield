<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    $email = 'test-' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();

    expect(\App\Models\User::findOrFail(auth()->id())->hasRole('user'))->toBeTrue();

    $this->assertDatabaseHas('auth_audit_logs', [
        'user_id' => auth()->id(),
        'email' => $email,
        'event' => 'account_created',
    ]);
});
