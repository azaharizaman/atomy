<?php

/**
 * Emergency OPcache Reset Script
 * 
 * This script clears PHP OPcache to force recompilation of all cached files.
 * Use when file changes aren't being recognized due to aggressive caching.
 */

echo "OPcache Status Before Reset:\n";
echo "============================\n";

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status !== false) {
        echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
        echo "Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . "\n";
        echo "Cache hits: " . ($status['opcache_statistics']['hits'] ?? 0) . "\n";
        echo "Cache misses: " . ($status['opcache_statistics']['misses'] ?? 0) . "\n";
    } else {
        echo "OPcache is disabled\n";
    }
} else {
    echo "OPcache extension not loaded\n";
}

echo "\nResetting OPcache...\n";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✓ OPcache successfully reset\n";
    } else {
        echo "✗ OPcache reset failed\n";
        exit(1);
    }
} else {
    echo "✗ opcache_reset() function not available\n";
    exit(1);
}

echo "\nOPcache Status After Reset:\n";
echo "===========================\n";

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status !== false) {
        echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
        echo "Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . "\n";
    }
} else {
    echo "OPcache is disabled after reset\n";
}

echo "\n✓ Cache cleared successfully. PHP will recompile all files on next execution.\n";
