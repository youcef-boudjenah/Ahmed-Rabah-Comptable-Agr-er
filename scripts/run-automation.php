<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Modules\Automation\AutomationPipeline;

$cabinetId = isset($argv[1]) ? (int) $argv[1] : 1;
$withAi = !isset($argv[2]) || $argv[2] !== '--no-ai';

$admin = Database::fetchOne(
    'SELECT u.*, c.name AS cabinet_name FROM users u JOIN cabinets c ON c.id = u.cabinet_id WHERE u.cabinet_id = ? ORDER BY u.id LIMIT 1',
    [$cabinetId]
);
if (!$admin) {
    fwrite(STDERR, "No user for cabinet {$cabinetId}\n");
    exit(1);
}

$_SESSION['user'] = [
    'id' => (int) $admin['id'],
    'cabinet_id' => (int) $admin['cabinet_id'],
    'cabinet_name' => $admin['cabinet_name'],
    'name' => $admin['name'],
    'email' => $admin['email'],
    'role' => $admin['role'],
];

$result = AutomationPipeline::runFull($cabinetId, (int) $admin['id'], $withAi);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
