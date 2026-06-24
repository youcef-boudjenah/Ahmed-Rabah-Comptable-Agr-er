<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Auth;
use App\Core\Database;

final class SettingsService
{
    private const DEFAULTS = [
        'auto_ai_classify' => true,
        'auto_ai_review_pipeline' => true,
        'auto_pdf_on_approve' => true,
        'auto_sync_tasks' => true,
        'auto_ocr_on_upload' => true,
        'alert_days_before' => 7,
        'pipeline_with_ai_default' => true,
        'collaborateur_can_approve' => true,
        'collaborateur_can_submit' => false,
    ];

    /** @return array<string, mixed> */
    public static function all(?int $cabinetId = null): array
    {
        $cabinetId = $cabinetId ?? Auth::cabinetId();
        $row = Database::fetchOne('SELECT settings_json FROM cabinet_settings WHERE cabinet_id = ?', [$cabinetId]);
        if (!$row) {
            return self::DEFAULTS;
        }
        $saved = json_decode($row['settings_json'], true) ?: [];
        return array_merge(self::DEFAULTS, $saved);
    }

    public static function get(string $key, mixed $default = null, ?int $cabinetId = null): mixed
    {
        $all = self::all($cabinetId);
        return $all[$key] ?? $default;
    }

    public static function bool(string $key, ?int $cabinetId = null): bool
    {
        return (bool) self::get($key, false, $cabinetId);
    }

    /** @param array<string, mixed> $patch */
    public static function update(array $patch): void
    {
        $cabinetId = Auth::cabinetId();
        $current = self::all($cabinetId);
        $merged = array_merge($current, $patch);
        $exists = Database::fetchOne('SELECT cabinet_id FROM cabinet_settings WHERE cabinet_id = ?', [$cabinetId]);
        if ($exists) {
            Database::query(
                'UPDATE cabinet_settings SET settings_json = ?, updated_at = NOW() WHERE cabinet_id = ?',
                [json_encode($merged, JSON_UNESCAPED_UNICODE), $cabinetId]
            );
        } else {
            Database::insert(
                'INSERT INTO cabinet_settings (cabinet_id, settings_json, updated_at) VALUES (?, ?, NOW())',
                [$cabinetId, json_encode($merged, JSON_UNESCAPED_UNICODE)]
            );
        }
    }
}
