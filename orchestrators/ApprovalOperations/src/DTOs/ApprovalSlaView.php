<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * SLA snapshot for UI (aggregated later from Workflow + instance metadata).
 */
final readonly class ApprovalSlaView
{
    public function __construct(
        public ?string $dueAtIso8601,
        public ?int $secondsRemaining,
    ) {
    }
}
