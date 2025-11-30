<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap file for Nexus\Manufacturing package.
 * 
 * This file loads the monorepo autoloader and registers the test namespace.
 */

// Load the monorepo autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Register the test namespace with the autoloader
spl_autoload_register(static function (string $class): void {
    $prefix = 'Nexus\\Manufacturing\\Tests\\';
    $baseDir = __DIR__ . '/';
    
    // Check if the class uses our prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Convert namespace separators to directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require $file;
    }
});
