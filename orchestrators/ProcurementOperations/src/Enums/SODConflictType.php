<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Segregation of Duties (SOD) conflict types in procurement.
 *
 * These define incompatible role combinations that violate
 * internal controls and create fraud risk.
 */
enum SODConflictType: string
{
    /**
     * Same person cannot create and approve.
     * Prevents self-approval of requisitions/POs.
     */
    case REQUESTOR_APPROVER = 'REQUESTOR_APPROVER';

    /**
     * Same person cannot approve and receive goods.
     * Prevents receiving without proper authorization.
     */
    case APPROVER_RECEIVER = 'APPROVER_RECEIVER';

    /**
     * Same person cannot receive goods and process payment.
     * Prevents payment for non-received goods.
     */
    case RECEIVER_PAYER = 'RECEIVER_PAYER';

    /**
     * Same person cannot create vendor and process payment.
     * Prevents fictitious vendor fraud.
     */
    case VENDOR_CREATOR_PAYER = 'VENDOR_CREATOR_PAYER';

    /**
     * Same person cannot modify pricing and approve.
     * Prevents unauthorized price changes.
     */
    case PRICING_APPROVER = 'PRICING_APPROVER';

    /**
     * Same person cannot create and approve journal entries.
     * Prevents unauthorized GL postings.
     */
    case JOURNAL_CREATOR_APPROVER = 'JOURNAL_CREATOR_APPROVER';

    /**
     * Same person cannot manage vendors and approve payments.
     * Prevents collusion with vendors.
     */
    case VENDOR_MANAGER_PAYMENT_APPROVER = 'VENDOR_MANAGER_PAYMENT_APPROVER';

    /**
     * Same person cannot create PO and match invoices.
     * Prevents invoice manipulation.
     */
    case PO_CREATOR_INVOICE_MATCHER = 'PO_CREATOR_INVOICE_MATCHER';

    /**
     * Get the conflicting roles for this SOD type.
     *
     * @return array{0: string, 1: string} Pair of conflicting roles
     */
    public function getConflictingRoles(): array
    {
        return match ($this) {
            self::REQUESTOR_APPROVER => ['procurement.requestor', 'procurement.approver'],
            self::APPROVER_RECEIVER => ['procurement.approver', 'warehouse.receiver'],
            self::RECEIVER_PAYER => ['warehouse.receiver', 'finance.payment_processor'],
            self::VENDOR_CREATOR_PAYER => ['vendor.creator', 'finance.payment_processor'],
            self::PRICING_APPROVER => ['procurement.pricing_manager', 'procurement.approver'],
            self::JOURNAL_CREATOR_APPROVER => ['finance.journal_creator', 'finance.journal_approver'],
            self::VENDOR_MANAGER_PAYMENT_APPROVER => ['vendor.manager', 'finance.payment_approver'],
            self::PO_CREATOR_INVOICE_MATCHER => ['procurement.po_creator', 'finance.invoice_matcher'],
        };
    }

    /**
     * Get the risk level of this SOD conflict.
     *
     * @return string HIGH, MEDIUM, or LOW
     */
    public function getRiskLevel(): string
    {
        return match ($this) {
            self::REQUESTOR_APPROVER,
            self::VENDOR_CREATOR_PAYER,
            self::RECEIVER_PAYER => 'HIGH',

            self::APPROVER_RECEIVER,
            self::JOURNAL_CREATOR_APPROVER,
            self::VENDOR_MANAGER_PAYMENT_APPROVER => 'MEDIUM',

            self::PRICING_APPROVER,
            self::PO_CREATOR_INVOICE_MATCHER => 'LOW',
        };
    }

    /**
     * Get a human-readable description of the conflict.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::REQUESTOR_APPROVER => 'Same person cannot create and approve requisitions/POs',
            self::APPROVER_RECEIVER => 'Same person cannot approve POs and receive goods',
            self::RECEIVER_PAYER => 'Same person cannot receive goods and process payments',
            self::VENDOR_CREATOR_PAYER => 'Same person cannot create vendors and process payments',
            self::PRICING_APPROVER => 'Same person cannot modify pricing and approve changes',
            self::JOURNAL_CREATOR_APPROVER => 'Same person cannot create and approve journal entries',
            self::VENDOR_MANAGER_PAYMENT_APPROVER => 'Same person cannot manage vendors and approve payments',
            self::PO_CREATOR_INVOICE_MATCHER => 'Same person cannot create POs and match invoices',
        };
    }

    /**
     * Get all HIGH risk conflict types.
     *
     * @return array<self>
     */
    public static function highRiskConflicts(): array
    {
        return array_filter(
            self::cases(),
            fn(self $type) => $type->getRiskLevel() === 'HIGH'
        );
    }

    /**
     * Find conflict type for a pair of roles.
     *
     * @param string $role1 First role
     * @param string $role2 Second role
     * @return self|null The conflict type if found, null otherwise
     */
    public static function findConflict(string $role1, string $role2): ?self
    {
        foreach (self::cases() as $type) {
            $conflicting = $type->getConflictingRoles();
            if (
                ($conflicting[0] === $role1 && $conflicting[1] === $role2) ||
                ($conflicting[0] === $role2 && $conflicting[1] === $role1)
            ) {
                return $type;
            }
        }

        return null;
    }
}
