<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_screen_returns_404(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_registration_post_returns_404(): void
    {
        $response = $this->post('/register', [
            'name' => 'Usuario Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(404);
    }
}
