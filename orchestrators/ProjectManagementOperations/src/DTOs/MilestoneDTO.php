<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

final readonly class MilestoneDTO
{
    public function __construct(
        public string $id,
        public string $projectId,
        public string $name,
        public \DateTimeImmutable $dueDate,
        public ?\DateTimeImmutable $completedAt,
        public bool $isBillable
    ) {
    }
}
