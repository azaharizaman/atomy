<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

/**
 * No active approval template exists for the subject type (maps to HTTP 404 at L3).
 */
final class ApprovalTemplateNotFoundException extends \RuntimeException
{
    public static function forSubjectType(string $tenantId, string $subjectType): self
    {
        return new self(\sprintf(
            'No approval template for subject type %s (tenant %s).',
            $subjectType,
            $tenantId
        ));
    }
}
