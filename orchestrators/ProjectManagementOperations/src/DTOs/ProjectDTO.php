<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

final readonly class ProjectDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public string $status
    ) {
    }
}
