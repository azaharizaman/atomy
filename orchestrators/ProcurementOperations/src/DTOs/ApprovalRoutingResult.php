<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\ApprovalLevel;

/**
 * Result DTO for approval routing determination.
 *
 * Contains the complete approval chain with all approvers,
 * their levels, and any delegation information.
 */
final readonly class ApprovalRoutingResult
{
    /**
     * @param bool $success Whether routing was determined successfully
     * @param ApprovalLevel $requiredLevel Minimum approval level required
     * @param array<int, array{
     *     level: int,
     *     approverId: string,
     *     approverName: string,
     *     delegatedFrom: ?string,
     *     delegatedFromName: ?string,
     *     approvalLimit: int,
     *     role: string
     * }> $approvalChain Ordered list of approvers
     * @param int $escalationTimeoutHours Hours before auto-escalation
     * @param string|null $errorMessage Error message if routing failed
     * @param array<string, mixed> $routingReason Explanation of routing decision
     */
    public function __construct(
        public bool $success,
        public ApprovalLevel $requiredLevel,
        public array $approvalChain,
        public int $escalationTimeoutHours = 48,
        public ?string $errorMessage = null,
        public array $routingReason = [],
    ) {}

    /**
     * Create a successful routing result.
     *
     * @param ApprovalLevel $requiredLevel
     * @param array<int, array{
     *     level: int,
     *     approverId: string,
     *     approverName: string,
     *     delegatedFrom: ?string,
     *     delegatedFromName: ?string,
     *     approvalLimit: int,
     *     role: string
     * }> $approvalChain
     * @param int $escalationTimeoutHours
     * @param array<string, mixed> $routingReason
     */
    public static function success(
        ApprovalLevel $requiredLevel,
        array $approvalChain,
        int $escalationTimeoutHours = 48,
        array $routingReason = []
    ): self {
        return new self(
            success: true,
            requiredLevel: $requiredLevel,
            approvalChain: $approvalChain,
            escalationTimeoutHours: $escalationTimeoutHours,
            routingReason: $routingReason,
        );
    }

    /**
     * Create a failed routing result.
     */
    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            requiredLevel: ApprovalLevel::LEVEL_1,
            approvalChain: [],
            errorMessage: $errorMessage,
        );
    }

    /**
     * Get the first approver in the chain.
     *
     * @return array{
     *     level: int,
     *     approverId: string,
     *     approverName: string,
     *     delegatedFrom: ?string,
     *     delegatedFromName: ?string,
     *     approvalLimit: int,
     *     role: string
     * }|null
     */
    public function getFirstApprover(): ?array
    {
        return $this->approvalChain[0] ?? null;
    }

    /**
     * Get total number of approvers required.
     */
    public function getApproverCount(): int
    {
        return count($this->approvalChain);
    }

    /**
     * Check if delegation was applied in the chain.
     */
    public function hasDelegation(): bool
    {
        foreach ($this->approvalChain as $approver) {
            if ($approver['delegatedFrom'] !== null) {
                return true;
            }
        }
        return false;
    }
}
