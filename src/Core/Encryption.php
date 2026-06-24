<?php

declare(strict_types=1);

namespace App\Core;

final class Encryption
{
    private static function key(): string
    {
        $config = require ROOT_PATH . '/config/app.php';
        return hash('sha256', $config['encryption_key'], true);
    }

    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < 17) {
            return $value;
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? $value : $plain;
    }
}
