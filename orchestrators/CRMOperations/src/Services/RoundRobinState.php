<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use ArrayObject;
use Nexus\CRMOperations\Contracts\RoundRobinStateInterface;

/**
 * Default implementation of RoundRobinStateInterface.
 * Manages state externally to avoid mutable reference issues.
 * Uses ArrayObject for mutable state while keeping the class readonly.
 */
final readonly class RoundRobinState implements RoundRobinStateInterface
{
    private ArrayObject $indices;

    public function __construct()
    {
        $this->indices = new ArrayObject();
    }

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
        $this->indices->offsetUnset($key);
    }

    public function resetAll(): void
    {
        $this->indices = new ArrayObject();
    }
}
