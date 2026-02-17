<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Pipelines\Leave;

/**
 * Leave application pipeline runner.
 */
final readonly class ApplyLeavePipeline
{
    /**
     * @param array<int,callable(array):array> $steps
     */
    public function __construct(
        private array $steps = []
    ) {}

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function process(array $payload): array
    {
        $state = $payload;

        foreach ($this->steps as $step) {
            $state = $step($state);
        }

        return $state;
    }
}
