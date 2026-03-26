<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

use Nexus\ApprovalOperations\Exceptions\RecordApprovalDecisionValidationException;

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
        public ?string $attachmentStorageKey = null,
    ) {
        if (\trim($this->tenantId) === '') {
            throw RecordApprovalDecisionValidationException::emptyTenantId();
        }
        if (\trim($this->instanceId) === '') {
            throw RecordApprovalDecisionValidationException::emptyInstanceId();
        }
        if (\trim($this->actorPrincipalId) === '') {
            throw RecordApprovalDecisionValidationException::emptyActorPrincipalId();
        }
    }
}
