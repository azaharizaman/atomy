<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

use Nexus\ApprovalOperations\Exceptions\InvalidStartApprovalCommandException;

/**
 * Start a new operational approval instance.
 *
 * Tenant id is a non-empty string (ULID in Atomy-Q); passed through to persistence and PolicyEngine mapping.
 */
final readonly class StartOperationalApprovalCommand
{
    public string $tenantId;

    public string $initiatorPrincipalId;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $tenantId,
        public ApprovalSubjectRef $subject,
        string $initiatorPrincipalId,
        public array $context = [],
    ) {
        $tenantId = \trim($tenantId);
        $initiatorPrincipalId = \trim($initiatorPrincipalId);
        if ($tenantId === '') {
            throw InvalidStartApprovalCommandException::fromEmptyTenantId();
        }
        if ($initiatorPrincipalId === '') {
            throw InvalidStartApprovalCommandException::fromEmptyInitiatorPrincipalId();
        }
        $this->tenantId = $tenantId;
        $this->initiatorPrincipalId = $initiatorPrincipalId;
    }
}
