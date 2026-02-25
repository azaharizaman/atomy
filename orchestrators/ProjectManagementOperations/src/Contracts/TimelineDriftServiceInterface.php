<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\ProjectManagementOperations\DTOs\TimelineHealthDTO;

interface TimelineDriftServiceInterface
{
    public function calculate(string $projectId, ?\DateTimeImmutable $now = null): TimelineHealthDTO;
}
