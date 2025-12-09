<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\ApprovalLevel;

/**
 * Context DTO for approval chain operations.
 *
 * Aggregates all data needed for approval workflow decisions.
 */
final readonly class ApprovalChainContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $documentId Document being approved
     * @param string $documentType Document type
     * @param int $amountCents Amount in cents
     * @param string $currency Currency code
     * @param string $requesterId Requester user ID
     * @param string $departmentId Department ID
     * @param ApprovalLevel $requiredLevel Required approval level
     * @param array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     managerId: ?string,
     *     spendLimitCents: int,
     *     roles: array<string>
     * }|null $requesterInfo Requester information
     * @param array{
     *     budgetId: string,
     *     availableCents: int,
     *     isAvailable: bool
     * }|null $budgetInfo Budget availability information
     * @param array{
     *     configuredThresholds: array<int, int>,
     *     escalationHours: int
     * } $approvalSettings Approval configuration from settings
     * @param array<int, array{
     *     approverId: string,
     *     approverName: string,
     *     originalApproverId: ?string,
     *     isDelegated: bool
     * }> $resolvedApprovers Resolved approvers after delegation
     */
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $documentType,
        public int $amountCents,
        public string $currency,
        public string $requesterId,
        public string $departmentId,
        public ApprovalLevel $requiredLevel,
        public ?array $requesterInfo = null,
        public ?array $budgetInfo = null,
        public array $approvalSettings = [],
        public array $resolvedApprovers = [],
    ) {}

    /**
     * Check if budget is available for this amount.
     */
    public function isBudgetAvailable(): bool
    {
        return $this->budgetInfo !== null && $this->budgetInfo['isAvailable'];
    }

    /**
     * Get amount in decimal format.
     */
    public function getAmountDecimal(): float
    {
        return $this->amountCents / 100;
    }

    /**
     * Check if requester has sufficient spend limit.
     */
    public function requesterHasSpendLimit(): bool
    {
        if ($this->requesterInfo === null) {
            return false;
        }

        return $this->requesterInfo['spendLimitCents'] >= $this->amountCents;
    }
}
