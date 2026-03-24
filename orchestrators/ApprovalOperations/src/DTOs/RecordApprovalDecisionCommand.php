<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Record an approve/reject (or similar) decision on an instance.
 */
final readonly class RecordApprovalDecisionCommand
{
    public function __construct(
        public string $tenantId,
        public string $instanceId,
        public string $actorPrincipalId,
        public OperationalApprovalDecision $decision,
        public ?string $comment = null,
    ) {
        if (\trim($this->tenantId) === '') {
            throw new \InvalidArgumentException('tenantId cannot be empty.');
        }
        if (\trim($this->instanceId) === '') {
            throw new \InvalidArgumentException('instanceId cannot be empty.');
        }
        if (\trim($this->actorPrincipalId) === '') {
            throw new \InvalidArgumentException('actorPrincipalId cannot be empty.');
        }
    }
}
