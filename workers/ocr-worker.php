<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Queue;
use App\Modules\Documents\OcrService;

$max = (int) ($argv[1] ?? 10);
for ($i = 0; $i < $max; $i++) {
    $job = Queue::claimNext('OCR_EXTRACT');
    if (!$job) {
        echo "No pending OCR jobs.\n";
        break;
    }
    try {
        OcrService::processDocument((int) $job['payload']['document_id']);
        Queue::complete((int) $job['id']);
        echo "Processed job #{$job['id']}\n";
    } catch (Throwable $e) {
        Queue::fail((int) $job['id'], $e->getMessage());
        echo "Failed job #{$job['id']}: {$e->getMessage()}\n";
    }
}
