<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO for requisition operations.
 *
 * Aggregates data from multiple packages needed for requisition workflow.
 */
final readonly class RequisitionContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $requisitionId Requisition ID
     * @param string $requisitionNumber Requisition number
     * @param string $status Current status
     * @param string $requestedBy Requester user ID
     * @param string $departmentId Department ID
     * @param int $totalAmountCents Total amount in cents
     * @param string $currency Currency code
     * @param array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     estimatedUnitPriceCents: int,
     *     uom: string,
     *     preferredVendorId: ?string,
     *     accountCode: ?string,
     *     costCenterId: ?string
     * }> $lineItems Line items
     * @param array{
     *     userId: string,
     *     userName: string,
     *     email: string,
     *     departmentName: string
     * }|null $requesterInfo Requester information
     * @param array{
     *     budgetId: string,
     *     budgetName: string,
     *     availableAmountCents: int,
     *     commitmentId: ?string
     * }|null $budgetInfo Budget information
     * @param array{
     *     workflowInstanceId: string,
     *     currentStep: string,
     *     pendingApprovers: array<string>
     * }|null $approvalInfo Approval workflow information
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $approvedAt Approval timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $requisitionId,
        public string $requisitionNumber,
        public string $status,
        public string $requestedBy,
        public string $departmentId,
        public int $totalAmountCents,
        public string $currency,
        public array $lineItems,
        public ?array $requesterInfo = null,
        public ?array $budgetInfo = null,
        public ?array $approvalInfo = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $approvedAt = null,
    ) {}
}
