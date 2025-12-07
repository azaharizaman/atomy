<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Procure-to-Pay saga step identifier.
 */
enum P2PStep: string
{
    case CREATE_REQUISITION = 'create_requisition';
    case APPROVE_REQUISITION = 'approve_requisition';
    case CREATE_PURCHASE_ORDER = 'create_purchase_order';
    case APPROVE_PURCHASE_ORDER = 'approve_purchase_order';
    case SEND_TO_VENDOR = 'send_to_vendor';
    case RECEIVE_GOODS = 'receive_goods';
    case THREE_WAY_MATCH = 'three_way_match';
    case CREATE_ACCRUAL = 'create_accrual';
    case PROCESS_PAYMENT = 'process_payment';
    case REVERSE_ACCRUAL = 'reverse_accrual';

    /**
     * Get the step order/sequence.
     */
    public function getOrder(): int
    {
        return match ($this) {
            self::CREATE_REQUISITION => 1,
            self::APPROVE_REQUISITION => 2,
            self::CREATE_PURCHASE_ORDER => 3,
            self::APPROVE_PURCHASE_ORDER => 4,
            self::SEND_TO_VENDOR => 5,
            self::RECEIVE_GOODS => 6,
            self::THREE_WAY_MATCH => 7,
            self::CREATE_ACCRUAL => 8,
            self::PROCESS_PAYMENT => 9,
            self::REVERSE_ACCRUAL => 10,
        };
    }

    /**
     * Check if this step has compensation logic.
     */
    public function hasCompensation(): bool
    {
        return match ($this) {
            self::CREATE_REQUISITION => true,      // Cancel requisition
            self::APPROVE_REQUISITION => true,     // Revert to pending
            self::CREATE_PURCHASE_ORDER => true,   // Cancel PO
            self::APPROVE_PURCHASE_ORDER => true,  // Revert to pending
            self::SEND_TO_VENDOR => true,          // Send cancellation notice
            self::RECEIVE_GOODS => true,           // Reverse GR
            self::THREE_WAY_MATCH => true,         // Unmatch
            self::CREATE_ACCRUAL => true,          // Reverse accrual
            self::PROCESS_PAYMENT => false,        // Cannot compensate payment (manual intervention)
            self::REVERSE_ACCRUAL => false,        // Final step, no compensation
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE_REQUISITION => 'Create Requisition',
            self::APPROVE_REQUISITION => 'Approve Requisition',
            self::CREATE_PURCHASE_ORDER => 'Create Purchase Order',
            self::APPROVE_PURCHASE_ORDER => 'Approve Purchase Order',
            self::SEND_TO_VENDOR => 'Send to Vendor',
            self::RECEIVE_GOODS => 'Receive Goods',
            self::THREE_WAY_MATCH => '3-Way Matching',
            self::CREATE_ACCRUAL => 'Create Accrual',
            self::PROCESS_PAYMENT => 'Process Payment',
            self::REVERSE_ACCRUAL => 'Reverse Accrual',
        };
    }

    /**
     * Get step description.
     */
    public function description(): string
    {
        return match ($this) {
            self::CREATE_REQUISITION => 'Creates a purchase requisition for required materials or services',
            self::APPROVE_REQUISITION => 'Routes requisition through approval workflow',
            self::CREATE_PURCHASE_ORDER => 'Converts approved requisition to a purchase order',
            self::APPROVE_PURCHASE_ORDER => 'Routes purchase order through approval workflow',
            self::SEND_TO_VENDOR => 'Transmits purchase order to selected vendor',
            self::RECEIVE_GOODS => 'Records receipt of goods/services against purchase order',
            self::THREE_WAY_MATCH => 'Validates consistency between PO, goods receipt, and vendor invoice',
            self::CREATE_ACCRUAL => 'Creates accrual entry for goods received but not yet invoiced',
            self::PROCESS_PAYMENT => 'Processes payment to vendor for matched invoice',
            self::REVERSE_ACCRUAL => 'Reverses accrual entry when invoice is received and matched',
        };
    }

    /**
     * Get the timeout for this step in seconds.
     */
    public function getTimeout(): int
    {
        return match ($this) {
            self::APPROVE_REQUISITION, self::APPROVE_PURCHASE_ORDER => 86400 * 7, // 7 days for approvals
            self::RECEIVE_GOODS => 86400 * 30, // 30 days for goods receipt
            self::PROCESS_PAYMENT => 3600, // 1 hour for payment processing
            default => 300, // 5 minutes for other steps
        };
    }
}
