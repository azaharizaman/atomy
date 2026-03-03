<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface StorageDriverInterface
{
    public function exists(string $path): bool;

    public function delete(string $path): void;
}
