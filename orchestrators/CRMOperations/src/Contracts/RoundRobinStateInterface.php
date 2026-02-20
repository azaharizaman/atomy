<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * State container for RoundRobinStrategy to avoid mutable references.
 */
interface RoundRobinStateInterface
{
    public function getIndex(string $key): int;

    public function setIndex(string $key, int $index): void;

    public function reset(string $key): void;

    public function resetAll(): void;
}
