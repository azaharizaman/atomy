<?php

declare(strict_types=1);

$autoloaders = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;

        return;
    }
}

fwrite(STDERR, "Unable to locate Composer autoloader for ProcurementML tests.\n");
exit(1);
