<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\RoundRobinStateInterface;

/**
 * Default implementation of RoundRobinStateInterface.
 * Manages state externally to avoid mutable reference issues.
 */
final class RoundRobinState implements RoundRobinStateInterface
{
    private array $indices = [];

    public function getIndex(string $key): int
    {
        return $this->indices[$key] ?? 0;
    }

    public function setIndex(string $key, int $index): void
    {
        $this->indices[$key] = $index;
    }

    public function reset(string $key): void
    {
        unset($this->indices[$key]);
    }

    public function resetAll(): void
    {
        $this->indices = [];
    }
}
