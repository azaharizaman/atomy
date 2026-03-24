<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Generic subject reference for operational approvals (not RFQ-domain-specific).
 */
final readonly class ApprovalSubjectRef
{
    public function __construct(
        public string $subjectType,
        public string $subjectId,
    ) {
        if (\trim($this->subjectType) === '') {
            throw new \InvalidArgumentException('subjectType cannot be empty.');
        }
        if (\trim($this->subjectId) === '') {
            throw new \InvalidArgumentException('subjectId cannot be empty.');
        }
    }
}
