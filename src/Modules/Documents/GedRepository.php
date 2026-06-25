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
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            return;
        }

        $newCategory = $data['category'] ?? ($doc['category'] ?? 'divers');
        if ($doc['client_id'] && $newCategory !== ($doc['category'] ?? 'divers')) {
            self::moveFileToCategory($id, $newCategory);
            $doc = DocumentRepository::find($id) ?: $doc;
        }

        Database::query(
            'UPDATE documents SET title = ?, category = ?, notes = ?, tags = ?, ged_status = ?, updated_at = NOW()
             WHERE id = ? AND cabinet_id = ?',
            [
                $data['title'],
                $newCategory,
                $data['notes'] ?? null,
                $data['tags'] ?? null,
                $data['ged_status'],
                $id,
                Auth::cabinetId(),
            ]
        );
    }

    public static function delete(int $id): bool
    {
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            return false;
        }
        if (!empty($doc['file_path']) && is_file($doc['file_path'])) {
            @unlink($doc['file_path']);
        }
        Database::query('DELETE FROM documents WHERE id = ? AND cabinet_id = ?', [$id, Auth::cabinetId()]);

        return true;
    }

    public static function moveFileToCategory(int $id, string $category): void
    {
        $doc = DocumentRepository::find($id);
        if (!$doc || !$doc['client_id']) {
            return;
        }

        $cat = array_key_exists($category, ClientFolderService::CATEGORIES) ? $category : 'divers';
        $clientId = (int) $doc['client_id'];
        $newDir = ClientFolderService::categoryPath($clientId, $cat);
        $basename = basename($doc['file_path']);
        $newPath = $newDir . '/' . $basename;

        if ($doc['file_path'] === $newPath) {
            return;
        }

        if (is_file($doc['file_path'])) {
            if (!@rename($doc['file_path'], $newPath)) {
                if (@copy($doc['file_path'], $newPath)) {
                    @unlink($doc['file_path']);
                }
            }
        }

        Database::query(
            'UPDATE documents SET category = ?, subfolder = ?, file_path = ?, updated_at = NOW() WHERE id = ? AND cabinet_id = ?',
            [$cat, $cat, $newPath, $id, Auth::cabinetId()]
        );
    }

    public static function reassignClient(int $id, int $clientId, ?string $category = null): void
    {
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            return;
        }

        $client = Database::fetchOne(
            'SELECT id FROM clients WHERE id = ? AND cabinet_id = ?',
            [$clientId, Auth::cabinetId()]
        );
        if (!$client) {
            throw new \InvalidArgumentException('Client introuvable');
        }

        $cat = $category ?? $doc['category'] ?? 'divers';
        $cat = array_key_exists($cat, ClientFolderService::CATEGORIES) ? $cat : 'divers';
        $newDir = ClientFolderService::categoryPath($clientId, $cat);
        $basename = basename($doc['file_path'] ?: ('doc_' . $id));
        $newPath = $newDir . '/' . $basename;

        if (!empty($doc['file_path']) && is_file($doc['file_path']) && $doc['file_path'] !== $newPath) {
            if (!@rename($doc['file_path'], $newPath)) {
                if (@copy($doc['file_path'], $newPath)) {
                    @unlink($doc['file_path']);
                }
            }
        }

        Database::query(
            'UPDATE documents SET client_id = ?, category = ?, subfolder = ?, file_path = ?, updated_at = NOW()
             WHERE id = ? AND cabinet_id = ?',
            [$clientId, $cat, $cat, $newPath, $id, Auth::cabinetId()]
        );
    }

    /** @param list<int> $ids */
    public static function bulk(int $clientId, array $ids, string $action, array $payload = []): int
    {
        $count = 0;
        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            $doc = DocumentRepository::find($id);
            if (!$doc || (int) ($doc['client_id'] ?? 0) !== $clientId) {
                continue;
            }

            switch ($action) {
                case 'delete':
                    if (self::delete($id)) {
                        $count++;
                    }
                    break;
                case 'archive':
                    self::updateMeta($id, [
                        'title' => $doc['title'] ?? $doc['original_name'],
                        'category' => $doc['category'] ?? 'divers',
                        'notes' => $doc['notes'] ?? '',
                        'tags' => $doc['tags'] ?? '',
                        'ged_status' => 'archive',
                    ]);
                    $count++;
                    break;
                case 'status':
                    self::updateMeta($id, [
                        'title' => $doc['title'] ?? $doc['original_name'],
                        'category' => $doc['category'] ?? 'divers',
                        'notes' => $doc['notes'] ?? '',
                        'tags' => $doc['tags'] ?? '',
                        'ged_status' => $payload['ged_status'] ?? 'a_traiter',
                    ]);
                    $count++;
                    break;
                case 'move':
                    self::moveFileToCategory($id, $payload['category'] ?? 'divers');
                    $count++;
                    break;
            }
        }

        return $count;
    }

    public static function recentForCabinet(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        return Database::fetchAll(
            "SELECT d.*, c.raison_sociale,
             (SELECT confidence FROM document_ocr_results WHERE document_id = d.id ORDER BY id DESC LIMIT 1) AS confidence
             FROM documents d
             LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.cabinet_id = ?
             ORDER BY COALESCE(d.updated_at, d.created_at) DESC
             LIMIT {$limit}",
            [Auth::cabinetId()]
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
