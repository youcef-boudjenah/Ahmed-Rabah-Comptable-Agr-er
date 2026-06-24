<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\View;

final class AuthController
{
    public static function loginForm(): void
    {
        if (Auth::check()) {
            View::redirect('/');
        }
        View::render('auth/login', ['title' => 'Connexion']);
    }

    public static function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (Auth::attempt($email, $password)) {
            View::redirect('/');
        }
        View::flash('error', 'Identifiants invalides.');
        View::redirect('/login');
    }

    public static function logout(): void
    {
        Auth::logout();
        View::redirect('/login');
    }
}
