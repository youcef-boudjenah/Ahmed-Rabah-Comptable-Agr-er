<?php

declare(strict_types=1);

namespace App\Core;

final class AuditLog
{
    public static function write(string $action, string $entity, ?int $entityId = null, ?array $meta = null): void
    {
        $user = Auth::user();
        Database::insert(
            'INSERT INTO audit_logs (cabinet_id, user_id, action, entity, entity_id, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $user['cabinet_id'] ?? null,
                $user['id'] ?? null,
                $action,
                $entity,
                $entityId,
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
