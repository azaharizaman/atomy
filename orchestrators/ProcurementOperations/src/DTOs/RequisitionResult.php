<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for requisition operations.
 */
final readonly class RequisitionResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $requisitionId Created/updated requisition ID
     * @param string|null $requisitionNumber Human-readable requisition number
     * @param string|null $status Current requisition status
     * @param string|null $message Human-readable result message
     * @param int|null $totalAmountCents Total requisition amount in cents
     * @param string|null $budgetCommitmentId Budget commitment ID (if created)
     * @param string|null $workflowInstanceId Approval workflow instance ID
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public ?string $requisitionId = null,
        public ?string $requisitionNumber = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?int $totalAmountCents = null,
        public ?string $budgetCommitmentId = null,
        public ?string $workflowInstanceId = null,
        public ?array $issues = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $requisitionId,
        string $requisitionNumber,
        string $status,
        int $totalAmountCents,
        ?string $budgetCommitmentId = null,
        ?string $workflowInstanceId = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            requisitionId: $requisitionId,
            requisitionNumber: $requisitionNumber,
            status: $status,
            message: $message,
            totalAmountCents: $totalAmountCents,
            budgetCommitmentId: $budgetCommitmentId,
            workflowInstanceId: $workflowInstanceId,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string, mixed>|null $issues
     */
    public static function failure(string $message, ?array $issues = null): self
    {
        return new self(
            success: false,
            message: $message,
            issues: $issues,
        );
    }
}
