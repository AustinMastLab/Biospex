<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('app.get.register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('app.post.register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));
});
