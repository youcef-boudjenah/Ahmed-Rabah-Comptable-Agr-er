<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Modules\Automation\BatchService;

$cabinetId = isset($argv[1]) ? (int) $argv[1] : 1;
$result = BatchService::recalculateCabinet($cabinetId);
echo "Batch recalculate cabinet {$cabinetId}: payroll={$result['payroll']}, sales={$result['sales']}\n";
