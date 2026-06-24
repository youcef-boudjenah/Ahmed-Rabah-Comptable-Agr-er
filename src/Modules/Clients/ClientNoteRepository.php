<?php

declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Auth;
use App\Core\Database;

final class ClientNoteRepository
{
    public static function forClient(int $clientId): array
    {
        return Database::fetchAll(
            'SELECT n.*, u.name AS author FROM client_notes n
             JOIN users u ON u.id = n.user_id
             WHERE n.client_id = ? ORDER BY n.is_pinned DESC, n.created_at DESC',
            [$clientId]
        );
    }

    public static function add(int $clientId, string $content, bool $pinned = false): void
    {
        Database::insert(
            'INSERT INTO client_notes (client_id, user_id, content, is_pinned) VALUES (?, ?, ?, ?)',
            [$clientId, Auth::id(), $content, $pinned ? 1 : 0]
        );
    }

    public static function delete(int $id, int $clientId): void
    {
        Database::query(
            'DELETE n FROM client_notes n JOIN clients c ON c.id = n.client_id
             WHERE n.id = ? AND n.client_id = ? AND c.cabinet_id = ?',
            [$id, $clientId, Auth::cabinetId()]
        );
    }
}
