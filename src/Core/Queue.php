<?php

declare(strict_types=1);

namespace App\Core;

final class Queue
{
    public static function push(string $type, array $payload): int
    {
        return Database::insert(
            'INSERT INTO job_queue (type, payload, status, attempts, created_at) VALUES (?, ?, ?, 0, NOW())',
            [$type, json_encode($payload, JSON_UNESCAPED_UNICODE), 'pending']
        );
    }

    public static function claimNext(string $type): ?array
    {
        $job = Database::fetchOne(
            "SELECT * FROM job_queue WHERE type = ? AND status = 'pending' ORDER BY id ASC LIMIT 1",
            [$type]
        );
        if (!$job) {
            return null;
        }
        Database::query(
            "UPDATE job_queue SET status = 'processing', attempts = attempts + 1, updated_at = NOW() WHERE id = ?",
            [$job['id']]
        );
        $job['payload'] = json_decode($job['payload'], true) ?: [];
        return $job;
    }

    public static function complete(int $id): void
    {
        Database::query("UPDATE job_queue SET status = 'done', updated_at = NOW() WHERE id = ?", [$id]);
    }

    public static function fail(int $id, string $error): void
    {
        Database::query(
            "UPDATE job_queue SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?",
            [$error, $id]
        );
    }
}
