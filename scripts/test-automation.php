<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Modules\Automation\AutomationPipeline;

$cabinet = Database::fetchOne('SELECT id FROM cabinets LIMIT 1');
$user = Database::fetchOne('SELECT id FROM users LIMIT 1');
if (!$cabinet || !$user) {
    echo "No cabinet/user\n";
    exit(1);
}

echo "Cabinet: {$cabinet['id']}, User: {$user['id']}\n";

try {
    $r = AutomationPipeline::runFull((int) $cabinet['id'], (int) $user['id'], false);
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
