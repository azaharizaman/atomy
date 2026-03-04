<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Nexus\\DataExchangeOperations\\' => __DIR__ . '/../orchestrators/DataExchangeOperations/src/',
        'Nexus\\InsightOperations\\' => __DIR__ . '/../orchestrators/InsightOperations/src/',
        'Nexus\\IntelligenceOperations\\' => __DIR__ . '/../orchestrators/IntelligenceOperations/src/',
        'Nexus\\ConnectivityOperations\\' => __DIR__ . '/../orchestrators/ConnectivityOperations/src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        if ($relative === false) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});
