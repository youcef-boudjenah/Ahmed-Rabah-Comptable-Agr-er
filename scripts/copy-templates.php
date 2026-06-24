<?php

declare(strict_types=1);

$src = dirname(__DIR__) . '/AHMED RABAH MOKHTAR';
$dest = dirname(__DIR__) . '/storage/templates';
$patterns = ['*.pdf', '*.PDF', '*.xlsx', '*.xlsm'];

if (!is_dir($src)) {
    echo "Sample folder not found.\n";
    exit(0);
}

foreach (glob($src . '/*') as $file) {
    if (is_file($file)) {
        copy($file, $dest . '/' . basename($file));
        echo 'Copied ' . basename($file) . "\n";
    }
}

echo "Templates copied.\n";
