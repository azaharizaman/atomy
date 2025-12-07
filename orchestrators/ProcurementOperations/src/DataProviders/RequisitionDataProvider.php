<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\Hrm\Contracts\EmployeeQueryInterface;
use Nexus\Budget\Contracts\BudgetQueryInterface;
use Nexus\Workflow\Contracts\WorkflowQueryInterface;
use Nexus\ProcurementOperations\DTOs\RequisitionContext;
use Nexus\ProcurementOperations\Exceptions\RequisitionException;

/**
 * Aggregates requisition data from multiple packages.
 *
 * Fetches requisition information along with related data from
 * HRM (requester), Budget (commitment), and Workflow (approval status).
 */
final readonly class RequisitionDataProvider
{
    public function __construct(
        private RequisitionQueryInterface $requisitionQuery,
        private ?EmployeeQueryInterface $employeeQuery = null,
        private ?BudgetQueryInterface $budgetQuery = null,
        private ?WorkflowQueryInterface $workflowQuery = null,
    ) {}

    /**
     * Get full requisition context for workflow operations.
     *
     * @throws RequisitionException
     */
    public function getContext(string $tenantId, string $requisitionId): RequisitionContext
    {
        $requisition = $this->requisitionQuery->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionException::notFound($requisitionId);
        }

        // Build line items array
        $lineItems = [];
        foreach ($requisition->getLineItems() as $index => $line) {
            $lineItems[$index] = [
                'lineId' => $line->getId(),
                'productId' => $line->getProductId(),
                'description' => $line->getDescription(),
                'quantity' => $line->getQuantity(),
                'estimatedUnitPriceCents' => $line->getEstimatedUnitPriceCents(),
                'uom' => $line->getUom(),
                'preferredVendorId' => $line->getPreferredVendorId(),
                'accountCode' => $line->getAccountCode(),
                'costCenterId' => $line->getCostCenterId(),
            ];
        }

        // Fetch requester info if HRM package available
        $requesterInfo = null;
        if ($this->employeeQuery !== null) {
            $employee = $this->employeeQuery->findByUserId($requisition->getRequestedBy());
            if ($employee !== null) {
                $requesterInfo = [
                    'userId' => $requisition->getRequestedBy(),
                    'userName' => $employee->getFullName(),
                    'email' => $employee->getEmail(),
                    'departmentName' => $employee->getDepartmentName() ?? 'Unknown',
                ];
            }
        }

        // Fetch budget info if Budget package available
        $budgetInfo = null;
        if ($this->budgetQuery !== null && $requisition->getBudgetId() !== null) {
            $budget = $this->budgetQuery->findById($requisition->getBudgetId());
            if ($budget !== null) {
                $commitment = $this->budgetQuery->findCommitmentByReference(
                    $requisition->getBudgetId(),
                    'requisition',
                    $requisitionId
                );

                $budgetInfo = [
                    'budgetId' => $budget->getId(),
                    'budgetName' => $budget->getName(),
                    'availableAmountCents' => $budget->getAvailableAmountCents(),
                    'commitmentId' => $commitment?->getId(),
                ];
            }
        }

        // Fetch approval workflow info if Workflow package available
        $approvalInfo = null;
        if ($this->workflowQuery !== null) {
            $workflow = $this->workflowQuery->findByReference('requisition', $requisitionId);
            if ($workflow !== null) {
                $approvalInfo = [
                    'workflowInstanceId' => $workflow->getId(),
                    'currentStep' => $workflow->getCurrentStep(),
                    'pendingApprovers' => $workflow->getPendingApprovers(),
                ];
            }
        }

        return new RequisitionContext(
            tenantId: $tenantId,
            requisitionId: $requisitionId,
            requisitionNumber: $requisition->getRequisitionNumber(),
            status: $requisition->getStatus()->value,
            requestedBy: $requisition->getRequestedBy(),
            departmentId: $requisition->getDepartmentId(),
            totalAmountCents: $requisition->getTotalAmountCents(),
            currency: $requisition->getCurrency(),
            lineItems: $lineItems,
            requesterInfo: $requesterInfo,
            budgetInfo: $budgetInfo,
            approvalInfo: $approvalInfo,
            createdAt: $requisition->getCreatedAt(),
            approvedAt: $requisition->getApprovedAt(),
        );
    }

    /**
     * Check if requisition is approved.
     */
    public function isApproved(string $requisitionId): bool
    {
        $requisition = $this->requisitionQuery->findById($requisitionId);

        if ($requisition === null) {
            return false;
        }

        return $requisition->getStatus()->value === 'approved';
    }

    /**
     * Get pending approvers for requisition.
     *
     * @return array<string>
     */
    public function getPendingApprovers(string $requisitionId): array
    {
        if ($this->workflowQuery === null) {
            return [];
        }

        $workflow = $this->workflowQuery->findByReference('requisition', $requisitionId);

        return $workflow?->getPendingApprovers() ?? [];
    }
}
