<?php

declare(strict_types=1);

/**
 * Bootstrap file for GeneralLedger package tests
 */

$vendorPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        return;
    }
}

die('Could not find vendor/autoload.php. Please run composer install.' . PHP_EOL);
