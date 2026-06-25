<?php

declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static string $locale = 'fr';

    /** @var array<string, string> */
    private static array $lines = [];

    /** @var array<string, string> */
    private static array $fallback = [];

    private static bool $loaded = false;

    private static bool $fallbackLoaded = false;

    public static function init(?string $locale = null): void
    {
        if ($locale !== null) {
            self::$locale = $locale;
        } elseif (isset($_SESSION['locale']) && is_string($_SESSION['locale'])) {
            self::$locale = $_SESSION['locale'];
        } elseif (isset($_COOKIE['locale']) && is_string($_COOKIE['locale'])) {
            self::$locale = $_COOKIE['locale'];
        }

        if (!in_array(self::$locale, ['fr', 'ar'], true)) {
            self::$locale = 'fr';
        }

        self::load();
    }

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, ['fr', 'ar'], true)) {
            return;
        }

        self::$locale = $locale;
        $_SESSION['locale'] = $locale;
        setcookie('locale', $locale, [
            'expires' => time() + 365 * 24 * 3600,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        self::$loaded = false;
        self::$fallbackLoaded = false;
        self::load();
    }

    public static function get(string $key, array $replace = []): string
    {
        if (!self::$loaded) {
            self::init();
        }

        $value = self::$lines[$key] ?? null;
        if ($value === null && self::$locale !== 'fr') {
            self::loadFallback();
            $value = self::$fallback[$key] ?? null;
        }
        if ($value === null) {
            $value = $key;
        }

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }

    public static function locale(): string
    {
        if (!self::$loaded) {
            self::init();
        }

        return self::$locale;
    }

    public static function isRtl(): bool
    {
        return self::locale() === 'ar';
    }

    private static function load(): void
    {
        self::$lines = self::readLocale(self::$locale);
        self::$loaded = true;
    }

    private static function loadFallback(): void
    {
        if (self::$fallbackLoaded) {
            return;
        }
        self::$fallback = self::readLocale('fr');
        self::$fallbackLoaded = true;
    }

    /** @return array<string, string> */
    private static function readLocale(string $locale): array
    {
        $lines = [];
        $dir = ROOT_PATH . '/lang/' . $locale;

        if (!is_dir($dir)) {
            return $lines;
        }

        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $group = basename($file, '.php');
            $translations = require $file;

            if (!is_array($translations)) {
                continue;
            }

            self::flattenInto($lines, $group, $translations);
        }

        return $lines;
    }

    /**
     * @param array<string, string> $target
     * @param array<string, mixed> $items
     */
    private static function flattenInto(array &$target, string $prefix, array $items): void
    {
        foreach ($items as $key => $value) {
            $fullKey = $prefix . '.' . $key;

            if (is_array($value)) {
                self::flattenInto($target, $fullKey, $value);
                continue;
            }

            if (is_string($value) || is_numeric($value)) {
                $target[$fullKey] = (string) $value;
            }
        }
    }
}
