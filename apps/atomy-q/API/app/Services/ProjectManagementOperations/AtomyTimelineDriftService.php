<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use DomainException;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\TimelineHealthDTO;

final readonly class AtomyTimelineDriftService implements TimelineDriftServiceInterface
{
    public function calculate(string $tenantId, string $projectId, ?\DateTimeImmutable $now = null): TimelineHealthDTO
    {
        throw new DomainException(sprintf(
            'Timeline drift calculation not implemented (tenant=%s, project=%s).',
            $tenantId,
            $projectId
        ));
    }
}
