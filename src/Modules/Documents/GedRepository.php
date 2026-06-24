<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;
use App\Modules\Automation\WorkflowService;

final class GedRepository
{
    public static function forClient(int $clientId, ?string $category = null): array
    {
        $sql = 'SELECT d.*, (SELECT confidence FROM document_ocr_results WHERE document_id = d.id ORDER BY id DESC LIMIT 1) AS confidence
                FROM documents d WHERE d.client_id = ? AND d.cabinet_id = ?';
        $params = [$clientId, Auth::cabinetId()];
        if ($category) {
            $sql .= ' AND d.category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY d.created_at DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function search(string $query, ?int $clientId = null): array
    {
        $like = '%' . $query . '%';
        $sql = 'SELECT d.*, c.raison_sociale FROM documents d
                LEFT JOIN clients c ON c.id = d.client_id
                WHERE d.cabinet_id = ? AND (d.original_name LIKE ? OR d.title LIKE ? OR d.tags LIKE ? OR d.notes LIKE ?)';
        $params = [Auth::cabinetId(), $like, $like, $like, $like];
        if ($clientId) {
            $sql .= ' AND d.client_id = ?';
            $params[] = $clientId;
        }
        $sql .= ' ORDER BY d.created_at DESC LIMIT 50';
        return Database::fetchAll($sql, $params);
    }

    public static function cabinetOverview(): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.raison_sociale, c.secteur, c.folder_path,
             (SELECT COUNT(*) FROM documents d WHERE d.client_id = c.id) AS doc_count,
             (SELECT COUNT(*) FROM documents d WHERE d.client_id = c.id AND d.ged_status = ?) AS a_traiter
             FROM clients c WHERE c.cabinet_id = ? AND c.is_active = 1 ORDER BY c.raison_sociale',
            ['a_traiter', Auth::cabinetId()]
        );
    }

    public static function storeInClientFolder(int $clientId, array $file, string $category, ?string $title = null): int
    {
        $dir = ClientFolderService::categoryPath($clientId, $category);
        $safeName = preg_replace('/[^a-zA-Z0-9._\-\s]/', '_', $file['name']) ?? 'upload';
        $dest = $dir . '/' . date('Ymd_His') . '_' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Upload échoué');
        }

        $id = Database::insert(
            'INSERT INTO documents (cabinet_id, client_id, original_name, title, file_path, mime, file_size, category, subfolder, doc_type, status, ged_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)',
            [
                Auth::cabinetId(),
                $clientId,
                $file['name'],
                $title ?: $file['name'],
                $dest,
                $file['type'] ?? 'application/octet-stream',
                (int) ($file['size'] ?? 0),
                $category,
                $category,
                'pending',
                'a_traiter',
            ]
        );
        WorkflowService::queueOcrIfEnabled($id);
        return $id;
    }

    public static function updateMeta(int $id, array $data): void
    {
        Database::query(
            'UPDATE documents d JOIN clients c ON c.id = d.client_id
             SET d.title = ?, d.category = ?, d.notes = ?, d.tags = ?, d.ged_status = ?, d.updated_at = NOW()
             WHERE d.id = ? AND c.cabinet_id = ?',
            [
                $data['title'],
                $data['category'],
                $data['notes'] ?? null,
                $data['tags'] ?? null,
                $data['ged_status'],
                $id,
                Auth::cabinetId(),
            ]
        );
    }

    public static function stats(): array
    {
        $cabinetId = Auth::cabinetId();
        return [
            'total' => (int) Database::fetchOne('SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ?', [$cabinetId])['c'],
            'a_traiter' => (int) Database::fetchOne("SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ? AND ged_status = 'a_traiter'", [$cabinetId])['c'],
            'traite' => (int) Database::fetchOne("SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ? AND ged_status = 'traite'", [$cabinetId])['c'],
        ];
    }
}
