<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Onboarding;

use Nexus\Onboarding\Contracts\TaskRepositoryInterface;

final readonly class CompleteTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}
    
    public function handle(string $taskId): void
    {
        // Mark onboarding task as complete
        throw new \RuntimeException('Implementation pending');
    }
}
