<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        // '/' redirects to the dashboard, which is auth-guarded → guests land on /login.
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }
}
