<?php

require_once __DIR__ . '/../core/Auth.php';

class AuthController
{
    public function login(string $role): void
    {
        Auth::login($role);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
