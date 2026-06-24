<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetchOne(
            'SELECT u.*, c.name AS cabinet_name FROM users u JOIN cabinets c ON c.id = u.cabinet_id WHERE u.email = ?',
            [$email]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'cabinet_id' => (int) $user['cabinet_id'],
            'cabinet_name' => $user['cabinet_name'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        AuditLog::write('login', 'users', (int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function cabinetId(): int
    {
        return (int) (self::user()['cabinet_id'] ?? 0);
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            View::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if ((self::user()['role'] ?? '') !== 'admin') {
            View::flash('error', 'Accès réservé aux administrateurs.');
            View::redirect('/');
        }
    }

    public static function canApprove(): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::user()['role'] === 'admin') {
            return true;
        }
        return \App\Modules\Admin\SettingsService::bool('collaborateur_can_approve');
    }

    public static function canSubmit(): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::user()['role'] === 'admin') {
            return true;
        }
        return \App\Modules\Admin\SettingsService::bool('collaborateur_can_submit');
    }
}
