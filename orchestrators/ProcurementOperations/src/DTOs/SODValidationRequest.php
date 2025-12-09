<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Request to validate Segregation of Duties compliance.
 */
final readonly class SODValidationRequest
{
    /**
     * @param string $userId User to validate
     * @param string $action Action being performed
     * @param string $entityType Type of entity (requisition, po, invoice, payment)
     * @param string $entityId ID of the entity
     * @param array<string> $userRoles Current roles of the user
     * @param array<SODConflictType>|null $conflictsToCheck Specific conflicts to validate (null = all)
     * @param array<string, mixed> $metadata Additional context
     */
    public function __construct(
        public string $userId,
        public string $action,
        public string $entityType,
        public string $entityId,
        public array $userRoles = [],
        public ?array $conflictsToCheck = null,
        public array $metadata = [],
    ) {}

    /**
     * Create request for requisition approval.
     */
    public static function forRequisitionApproval(
        string $approverId,
        string $requisitionId,
        string $requestorId,
        array $approverRoles,
    ): self {
        return new self(
            userId: $approverId,
            action: 'approve_requisition',
            entityType: 'requisition',
            entityId: $requisitionId,
            userRoles: $approverRoles,
            conflictsToCheck: [SODConflictType::REQUESTOR_APPROVER],
            metadata: ['requestor_id' => $requestorId],
        );
    }

    /**
     * Create request for goods receipt.
     */
    public static function forGoodsReceipt(
        string $receiverId,
        string $poId,
        string $approverId,
        array $receiverRoles,
    ): self {
        return new self(
            userId: $receiverId,
            action: 'receive_goods',
            entityType: 'purchase_order',
            entityId: $poId,
            userRoles: $receiverRoles,
            conflictsToCheck: [SODConflictType::APPROVER_RECEIVER],
            metadata: ['approver_id' => $approverId],
        );
    }

    /**
     * Create request for payment processing.
     */
    public static function forPaymentProcessing(
        string $payerId,
        string $invoiceId,
        string $vendorId,
        string $receiverId,
        array $payerRoles,
    ): self {
        return new self(
            userId: $payerId,
            action: 'process_payment',
            entityType: 'invoice',
            entityId: $invoiceId,
            userRoles: $payerRoles,
            conflictsToCheck: [
                SODConflictType::RECEIVER_PAYER,
                SODConflictType::VENDOR_CREATOR_PAYER,
            ],
            metadata: [
                'vendor_id' => $vendorId,
                'receiver_id' => $receiverId,
            ],
        );
    }

    /**
     * Create request for invoice matching.
     */
    public static function forInvoiceMatching(
        string $matcherId,
        string $invoiceId,
        string $poCreatorId,
        array $matcherRoles,
    ): self {
        return new self(
            userId: $matcherId,
            action: 'match_invoice',
            entityType: 'invoice',
            entityId: $invoiceId,
            userRoles: $matcherRoles,
            conflictsToCheck: [SODConflictType::PO_CREATOR_INVOICE_MATCHER],
            metadata: ['po_creator_id' => $poCreatorId],
        );
    }
}
