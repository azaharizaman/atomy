<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use Nexus\Procurement\Exceptions\InvalidRequisitionDataException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Psr\Log\LoggerInterface;

/**
 * Manages purchase requisition lifecycle.
 * 
 * Enforces business rules:
 * - Requester cannot approve own requisition (BUS-PRO-0095)
 * - Approved requisitions are immutable
 * - Only approved requisitions can be converted to POs
 */
final readonly class RequisitionManager
{
    public function __construct(
        private RequisitionRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a new requisition.
     *
     * @param string $tenantId Tenant identifier
     * @param string $requesterId User creating the requisition
     * @param array{
     *   number: string,
     *   description: string,
     *   department: string,
     *   lines: array<array{item_code: string, description: string, quantity: float, unit: string, estimated_unit_price: float}>,
     *   metadata?: array
     * } $data Requisition data
     * @return RequisitionInterface
     * @throws InvalidRequisitionDataException
     */
    public function createRequisition(string $tenantId, string $requesterId, array $data): RequisitionInterface
    {
        // Auto-generate number if not provided
        if (!isset($data['number'])) {
            $data['number'] = $this->repository->generateNextNumber($tenantId);
        }

        $this->validateRequisitionData($data);

        $this->logger->info('Creating requisition', [
            'tenant_id' => $tenantId,
            'requester_id' => $requesterId,
            'number' => $data['number'],
        ]);

        $requisition = $this->repository->create($tenantId, $requesterId, $data);

        $this->logger->info('Requisition created', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisition->getId(),
            'number' => $requisition->getNumber(),
            'status' => $requisition->getStatus(),
        ]);

        return $requisition;
    }

    /**
     * Submit requisition for approval.
     *
     * @param string $requisitionId
     * @return RequisitionInterface
     * @throws RequisitionNotFoundException
     * @throws InvalidRequisitionStateException
     */
    public function submitForApproval(string $requisitionId): RequisitionInterface
    {
        $requisition = $this->repository->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionNotFoundException::forId($requisitionId);
        }

        if ($requisition->getStatus() !== 'draft') {
            throw InvalidRequisitionStateException::cannotApproveStatus(
                $requisitionId,
                $requisition->getStatus()
            );
        }

        $this->logger->info('Submitting requisition for approval', [
            'requisition_id' => $requisitionId,
            'number' => $requisition->getNumber(),
        ]);

        $updatedRequisition = $this->repository->updateStatus($requisitionId, 'pending_approval');

        return $updatedRequisition;
    }

    /**
     * Approve a requisition.
     *
     * Enforces business rule: Requester cannot approve their own requisition (BUS-PRO-0095).
     *
     * @param string $requisitionId
     * @param string $approverId User approving the requisition
     * @return RequisitionInterface
     * @throws RequisitionNotFoundException
     * @throws InvalidRequisitionStateException
     * @throws UnauthorizedApprovalException
     */
    public function approveRequisition(string $requisitionId, string $approverId): RequisitionInterface
    {
        $requisition = $this->repository->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionNotFoundException::forId($requisitionId);
        }

        if ($requisition->getStatus() !== 'pending_approval') {
            throw InvalidRequisitionStateException::cannotApproveStatus(
                $requisitionId,
                $requisition->getStatus()
            );
        }

        // BUS-PRO-0095: Requester cannot approve own requisition
        if ($requisition->getRequesterId() === $approverId) {
            throw UnauthorizedApprovalException::cannotApproveOwnRequisition($requisitionId, $approverId);
        }

        $this->logger->info('Approving requisition', [
            'requisition_id' => $requisitionId,
            'number' => $requisition->getNumber(),
            'approver_id' => $approverId,
        ]);

        $updatedRequisition = $this->repository->approve($requisitionId, $approverId);

        $this->logger->info('Requisition approved', [
            'requisition_id' => $requisitionId,
            'number' => $updatedRequisition->getNumber(),
            'status' => $updatedRequisition->getStatus(),
        ]);

        return $updatedRequisition;
    }

    /**
     * Reject a requisition.
     *
     * @param string $requisitionId
     * @param string $rejectorId User rejecting the requisition
     * @param string $reason Rejection reason
     * @return RequisitionInterface
     * @throws RequisitionNotFoundException
     * @throws InvalidRequisitionStateException
     */
    public function rejectRequisition(string $requisitionId, string $rejectorId, string $reason): RequisitionInterface
    {
        $requisition = $this->repository->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionNotFoundException::forId($requisitionId);
        }

        if ($requisition->getStatus() !== 'pending_approval') {
            throw InvalidRequisitionStateException::cannotApproveStatus(
                $requisitionId,
                $requisition->getStatus()
            );
        }

        $this->logger->info('Rejecting requisition', [
            'requisition_id' => $requisitionId,
            'number' => $requisition->getNumber(),
            'rejector_id' => $rejectorId,
            'reason' => $reason,
        ]);

        $updatedRequisition = $this->repository->reject($requisitionId, $rejectorId, $reason);

        return $updatedRequisition;
    }

    /**
     * Mark requisition as converted to PO.
     *
     * @param string $requisitionId
     * @param PurchaseOrderInterface $purchaseOrder
     * @return RequisitionInterface
     * @throws RequisitionNotFoundException
     * @throws InvalidRequisitionStateException
     */
    public function markAsConverted(string $requisitionId, PurchaseOrderInterface $purchaseOrder): RequisitionInterface
    {
        $requisition = $this->repository->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionNotFoundException::forId($requisitionId);
        }

        if ($requisition->getStatus() !== 'approved') {
            throw InvalidRequisitionStateException::cannotConvertStatus(
                $requisitionId,
                $requisition->getStatus()
            );
        }

        if ($requisition->isConverted()) {
            throw InvalidRequisitionStateException::alreadyConverted($requisitionId);
        }

        $this->logger->info('Marking requisition as converted', [
            'requisition_id' => $requisitionId,
            'number' => $requisition->getNumber(),
            'po_id' => $purchaseOrder->getId(),
            'po_number' => $purchaseOrder->getNumber(),
        ]);

        $updatedRequisition = $this->repository->markAsConverted($requisitionId, $purchaseOrder->getId());

        return $updatedRequisition;
    }

    /**
     * Get requisition by ID.
     *
     * @param string $requisitionId
     * @return RequisitionInterface
     * @throws RequisitionNotFoundException
     */
    public function getRequisition(string $requisitionId): RequisitionInterface
    {
        $requisition = $this->repository->findById($requisitionId);

        if ($requisition === null) {
            throw RequisitionNotFoundException::forId($requisitionId);
        }

        return $requisition;
    }

    /**
     * Get all requisitions for tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<RequisitionInterface>
     */
    public function getRequisitionsForTenant(string $tenantId, array $filters = []): array
    {
        return $this->repository->findByTenantId($tenantId, $filters);
    }

    /**
     * Get requisitions by status.
     *
     * @param string $tenantId
     * @param string $status
     * @return array<RequisitionInterface>
     */
    public function getRequisitionsByStatus(string $tenantId, string $status): array
    {
        return $this->repository->findByStatus($tenantId, $status);
    }

    /**
     * Validate requisition data.
     *
     * @param array $data
     * @throws InvalidRequisitionDataException
     */
    private function validateRequisitionData(array $data): void
    {
        if (!isset($data['lines']) || !is_array($data['lines']) || count($data['lines']) === 0) {
            throw InvalidRequisitionDataException::noLines();
        }

        if (!isset($data['number'])) {
            throw InvalidRequisitionDataException::missingRequiredField('number');
        }

        if (!isset($data['description'])) {
            throw InvalidRequisitionDataException::missingRequiredField('description');
        }

        if (!isset($data['department'])) {
            throw InvalidRequisitionDataException::missingRequiredField('department');
        }
    }
}
