<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;
use App\Modules\Automation\WorkflowService;

final class DocumentRepository
{
    public static function allForCabinet(): array
    {
        return Database::fetchAll(
            'SELECT d.*, c.raison_sociale,
             (SELECT confidence FROM document_ocr_results WHERE document_id = d.id ORDER BY id DESC LIMIT 1) AS confidence
             FROM documents d
             LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.cabinet_id = ? ORDER BY d.created_at DESC',
            [Auth::cabinetId()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT d.*, c.raison_sociale FROM documents d
             LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.id = ? AND d.cabinet_id = ?',
            [$id, Auth::cabinetId()]
        );
    }

    public static function ocrResult(int $documentId): ?array
    {
        $row = Database::fetchOne(
            'SELECT * FROM document_ocr_results WHERE document_id = ? ORDER BY id DESC LIMIT 1',
            [$documentId]
        );
        if ($row) {
            $row['extracted_json'] = json_decode($row['extracted_json'], true) ?: [];
        }
        return $row;
    }

    public static function storeUpload(array $file, ?int $clientId): int
    {
        $dir = ROOT_PATH . '/storage/uploads/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']) ?? 'upload';
        $dest = $dir . '/' . uniqid('doc_') . '_' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Upload failed');
        }

        $id = Database::insert(
            'INSERT INTO documents (cabinet_id, client_id, original_name, file_path, mime, status) VALUES (?, ?, ?, ?, ?, ?)',
            [Auth::cabinetId(), $clientId, $file['name'], $dest, $file['type'] ?? 'application/octet-stream', 'pending']
        );

        WorkflowService::queueOcrIfEnabled($id);
        return $id;
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::query('UPDATE documents SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
    }

    public static function updateDocType(int $id, string $docType, string $status): void
    {
        Database::query(
            'UPDATE documents SET doc_type = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$docType, $status, $id]
        );
    }

    public static function saveOcrResult(int $documentId, string $raw, array $extracted, float $confidence, string $source): void
    {
        Database::insert(
            'INSERT INTO document_ocr_results (document_id, raw_text, extracted_json, confidence, extraction_source) VALUES (?, ?, ?, ?, ?)',
            [$documentId, $raw, json_encode($extracted, JSON_UNESCAPED_UNICODE), $confidence, $source]
        );
    }
}
