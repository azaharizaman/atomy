<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

use Nexus\ApprovalOperations\Exceptions\InvalidApprovalSubjectRefException;

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
            throw InvalidApprovalSubjectRefException::emptySubjectType();
        }
        if (\trim($this->subjectId) === '') {
            throw InvalidApprovalSubjectRefException::emptySubjectId();
        }
    }
}
