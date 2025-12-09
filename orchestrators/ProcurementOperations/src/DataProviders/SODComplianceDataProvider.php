<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Hrm\Contracts\EmployeeQueryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;

/**
 * Data provider for SOD validation context.
 *
 * Aggregates user roles and entity ownership information
 * needed for segregation of duties checks.
 */
final readonly class SODComplianceDataProvider
{
    public function __construct(
        private UserQueryInterface $userQuery,
        private RoleQueryInterface $roleQuery,
        private RequisitionQueryInterface $requisitionQuery,
        private PurchaseOrderQueryInterface $poQuery,
        private ?EmployeeQueryInterface $employeeQuery = null,
    ) {}

    /**
     * Get all roles assigned to a user.
     *
     * @return array<string> List of role identifiers
     */
    public function getUserRoles(string $userId): array
    {
        $user = $this->userQuery->findById($userId);
        if ($user === null) {
            return [];
        }

        return $this->roleQuery->getRoleIdentifiersForUser($userId);
    }

    /**
     * Get the requestor ID for a requisition.
     */
    public function getRequisitionRequestorId(string $requisitionId): ?string
    {
        $requisition = $this->requisitionQuery->findById($requisitionId);

        return $requisition?->getRequestorId();
    }

    /**
     * Get the creator ID for a purchase order.
     */
    public function getPOCreatorId(string $poId): ?string
    {
        $po = $this->poQuery->findById($poId);

        return $po?->getCreatedById();
    }

    /**
     * Get the approver ID for a purchase order.
     */
    public function getPOApproverId(string $poId): ?string
    {
        $po = $this->poQuery->findById($poId);

        return $po?->getApprovedById();
    }

    /**
     * Get the vendor creator ID.
     */
    public function getVendorCreatorId(string $vendorId): ?string
    {
        // This would typically come from an audit log or vendor history
        // For now, we'll rely on the metadata passed in the request
        return null;
    }

    /**
     * Get receiver ID for a goods receipt on a PO.
     */
    public function getGoodsReceiverId(string $poId): ?string
    {
        $po = $this->poQuery->findById($poId);

        // Get the most recent goods receipt receiver
        $receipts = $po?->getGoodsReceipts() ?? [];
        if (empty($receipts)) {
            return null;
        }

        $lastReceipt = end($receipts);

        return $lastReceipt?->getReceivedById();
    }

    /**
     * Check if user has a specific role.
     */
    public function userHasRole(string $userId, string $roleIdentifier): bool
    {
        $roles = $this->getUserRoles($userId);

        return in_array($roleIdentifier, $roles, true);
    }

    /**
     * Check if two users are the same person.
     * Handles cases where user might have multiple IDs (employee ID vs user ID).
     */
    public function areSameUser(string $userId1, string $userId2): bool
    {
        if ($userId1 === $userId2) {
            return true;
        }

        // Check if they resolve to the same employee
        if ($this->employeeQuery !== null) {
            $employee1 = $this->employeeQuery->findByUserId($userId1);
            $employee2 = $this->employeeQuery->findByUserId($userId2);

            if ($employee1 !== null && $employee2 !== null) {
                return $employee1->getId() === $employee2->getId();
            }
        }

        return false;
    }

    /**
     * Get context for SOD validation.
     *
     * @return array<string, mixed>
     */
    public function getValidationContext(
        string $userId,
        string $entityType,
        string $entityId,
    ): array {
        $context = [
            'user_id' => $userId,
            'user_roles' => $this->getUserRoles($userId),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];

        switch ($entityType) {
            case 'requisition':
                $context['requestor_id'] = $this->getRequisitionRequestorId($entityId);
                break;

            case 'purchase_order':
                $context['creator_id'] = $this->getPOCreatorId($entityId);
                $context['approver_id'] = $this->getPOApproverId($entityId);
                $context['receiver_id'] = $this->getGoodsReceiverId($entityId);
                break;
        }

        return $context;
    }
}
