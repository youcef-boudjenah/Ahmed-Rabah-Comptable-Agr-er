<?php

declare(strict_types=1);

namespace App\Modules\Documents;

final class ClientFolderService
{
    public const CATEGORIES = [
        'paie' => 'Paie & RH',
        'social' => 'CNAS / CACOBATPH',
        'fiscal' => 'Fiscal (G50, G12)',
        'factures' => 'Factures',
        'banque' => 'Banque',
        'juridique' => 'Juridique',
        'divers' => 'Divers',
    ];

    public static function basePath(): string
    {
        return ROOT_PATH . '/storage/clients';
    }

    public static function clientPath(int $clientId): string
    {
        return self::basePath() . '/' . $clientId;
    }

    public static function categoryLabels(): array
    {
        $labels = [];
        foreach (array_keys(self::CATEGORIES) as $key) {
            $labels[$key] = __('ged.cat_' . $key);
        }
        return $labels;
    }

    public static function ensure(int $clientId): string
    {
        $path = self::clientPath($clientId);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            foreach (array_keys(self::CATEGORIES) as $cat) {
                mkdir($path . '/' . $cat, 0755, true);
            }
        }
        \App\Core\Database::query(
            'UPDATE clients SET folder_path = ? WHERE id = ?',
            [$path, $clientId]
        );
        return $path;
    }

    public static function categoryPath(int $clientId, string $category): string
    {
        $cat = array_key_exists($category, self::CATEGORIES) ? $category : 'divers';
        $path = self::ensure($clientId) . '/' . $cat;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /** @return array<string, array{label: string, count: int, size: int}> */
    public static function structure(int $clientId): array
    {
        self::ensure($clientId);
        $structure = [];
        foreach (self::CATEGORIES as $key => $label) {
            $dir = self::clientPath($clientId) . '/' . $key;
            $files = is_dir($dir) ? array_diff(scandir($dir) ?: [], ['.', '..']) : [];
            $size = 0;
            foreach ($files as $f) {
                $fp = $dir . '/' . $f;
                if (is_file($fp)) {
                    $size += filesize($fp);
                }
            }
            $dbCount = (int) (\App\Core\Database::fetchOne(
                'SELECT COUNT(*) AS c FROM documents WHERE client_id = ? AND category = ?',
                [$clientId, $key]
            )['c'] ?? 0);
            $structure[$key] = [
                'label' => __('ged.cat_' . $key),
                'count' => max(count($files), $dbCount),
                'size' => $size,
            ];
        }
        return $structure;
    }
}
