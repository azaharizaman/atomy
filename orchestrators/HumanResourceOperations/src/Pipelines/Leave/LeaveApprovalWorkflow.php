<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Pipelines\Leave;

/**
 * Leave approval workflow runner.
 */
final readonly class LeaveApprovalWorkflow
{
    /**
     * @param array<int,callable(array):array> $stages
     */
    public function __construct(
        private array $stages = []
    ) {}

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function run(array $context): array
    {
        $result = $context;

        foreach ($this->stages as $stage) {
            $result = $stage($result);
        }

        return $result;
    }
}
