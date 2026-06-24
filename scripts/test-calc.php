<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Modules\Automation\CalcEngine;

function assertEq(float $expected, float $actual, string $label): void
{
    if (abs($expected - $actual) > 0.02) {
        echo "FAIL $label: expected $expected, got $actual\n";
        exit(1);
    }
    echo "OK $label = $actual\n";
}

// Pure calc test without DB
$assiette = 173781.80;
$r22 = round($assiette * 0.345, 2);
$r98 = round($assiette * 0.005, 2);
$r38 = round($assiette * 0.0013, 2);
$total = round($r22 + $r98 + $r38, 2);
assertEq(59954.72, $r22, 'CNAS R22');
assertEq(868.91, $r98, 'CNAS R98');
assertEq(225.92, $r38, 'CNAS R38');
assertEq(61049.55, $total, 'CNAS total Jan 2026');

$assietteQ = 654567.25;
$cp = round($assietteQ * 0.1221, 2);
$ci = round($assietteQ * 0.0075, 2);
assertEq(79922.66, $cp, 'CACOBATPH congés');
assertEq(4909.25, $ci, 'CACOBATPH chômage');
assertEq(84831.91, round($cp + $ci, 2), 'CACOBATPH total Q1');

$irg = round(702522 * 0.30, 2);
assertEq(210756.60, $irg, 'G50 IRG acompte');

echo "\nAll calc tests passed.\n";
