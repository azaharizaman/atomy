<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $loader = require __DIR__ . '/../vendor/autoload.php';
} else {
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
}

$loader->addPsr4('Nexus\\Scheduler\\Tests\\', __DIR__ . '/');

date_default_timezone_set('UTC');
