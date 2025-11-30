<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Main procurement manager interface.
 *
 * Orchestrates all procurement operations including requisitions,
 * purchase orders, goods receipts, and vendor quotes.
 */
interface ProcurementManagerInterface
{
    /**
     * Create a new purchase requisition.
     *
     * @param string $tenantId Tenant ULID
     * @param string $requesterId Requester user ULID
     * @param array $data Requisition data including lines
     * @return RequisitionInterface
     * @throws \Nexus\Procurement\Exceptions\InvalidRequisitionDataException
     */
    public function createRequisition(string $tenantId, string $requesterId, array $data): RequisitionInterface;

    /**
     * Submit requisition for approval.
     *
     * @param string $requisitionId Requisition ULID
     * @return RequisitionInterface
     * @throws \Nexus\Procurement\Exceptions\RequisitionNotFoundException
     * @throws \Nexus\Procurement\Exceptions\InvalidRequisitionStateException
     */
    public function submitRequisitionForApproval(string $requisitionId): RequisitionInterface;

    /**
     * Approve a requisition.
     *
     * @param string $requisitionId Requisition ULID
     * @param string $approverId User ULID
     * @return RequisitionInterface
     * @throws \Nexus\Procurement\Exceptions\RequisitionNotFoundException
     * @throws \Nexus\Procurement\Exceptions\UnauthorizedApprovalException
     */
    public function approveRequisition(string $requisitionId, string $approverId): RequisitionInterface;

    /**
     * Reject a requisition.
     *
     * @param string $requisitionId Requisition ULID
     * @param string $rejectorId User ULID
     * @param string $reason Rejection reason
     * @return RequisitionInterface
     * @throws \Nexus\Procurement\Exceptions\RequisitionNotFoundException
     */
    public function rejectRequisition(string $requisitionId, string $rejectorId, string $reason): RequisitionInterface;

    /**
     * Convert approved requisition to purchase order.
     *
     * @param string $tenantId Tenant ULID
     * @param string $requisitionId Requisition ULID
     * @param string $creatorId User ULID creating the PO
     * @param array $poData PO data (vendor, terms, etc.)
     * @return PurchaseOrderInterface
     * @throws \Nexus\Procurement\Exceptions\RequisitionNotFoundException
     * @throws \Nexus\Procurement\Exceptions\InvalidRequisitionStateException
     * @throws \Nexus\Procurement\Exceptions\BudgetExceededException
     */
    public function convertRequisitionToPO(string $tenantId, string $requisitionId, string $creatorId, array $poData): PurchaseOrderInterface;

    /**
     * Create direct purchase order (bypass requisition).
     *
     * @param string $tenantId Tenant ULID
     * @param string $creatorId User ULID creating the PO
     * @param array $data PO data including lines
     * @return PurchaseOrderInterface
     * @throws \Nexus\Procurement\Exceptions\InvalidPurchaseOrderDataException
     */
    public function createDirectPO(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface;

    /**
     * Release purchase order to vendor.
     *
     * @param string $poId PO ULID
     * @param string $releasedBy User ULID releasing the PO
     * @return PurchaseOrderInterface
     * @throws \Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException
     */
    public function releasePO(string $poId, string $releasedBy): PurchaseOrderInterface;

    /**
     * Record goods receipt against purchase order.
     *
     * @param string $tenantId Tenant ULID
     * @param string $poId PO ULID
     * @param string $receiverId User ULID receiving the goods
     * @param array $receiptData GRN data including lines
     * @return GoodsReceiptNoteInterface
     * @throws \Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException
     * @throws \Nexus\Procurement\Exceptions\InvalidGoodsReceiptDataException
     */
    public function recordGoodsReceipt(string $tenantId, string $poId, string $receiverId, array $receiptData): GoodsReceiptNoteInterface;

    /**
     * Find requisition by ID.
     *
     * @param string $id Requisition ULID
     * @return RequisitionInterface
     * @throws \Nexus\Procurement\Exceptions\RequisitionNotFoundException
     */
    public function getRequisition(string $id): RequisitionInterface;

    /**
     * Find purchase order by ID.
     *
     * @param string $id PO ULID
     * @return PurchaseOrderInterface
     * @throws \Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException
     */
    public function getPurchaseOrder(string $id): PurchaseOrderInterface;

    /**
     * Find goods receipt note by ID.
     *
     * @param string $id GRN ULID
     * @return GoodsReceiptNoteInterface
     * @throws \Nexus\Procurement\Exceptions\GoodsReceiptNotFoundException
     */
    public function getGoodsReceipt(string $id): GoodsReceiptNoteInterface;

    /**
     * Perform three-way match between PO, GRN, and invoice.
     *
     * @param PurchaseOrderLineInterface $poLine
     * @param GoodsReceiptLineInterface $grnLine
     * @param array $invoiceLineData
     * @return array Match result with recommendation
     */
    public function performThreeWayMatch(
        PurchaseOrderLineInterface $poLine,
        GoodsReceiptLineInterface $grnLine,
        array $invoiceLineData
    ): array;

    /**
     * Authorize payment for goods receipt (requires 3-way match or manual override).
     *
     * @param string $grnId GRN ULID
     * @param string $authorizerId User ULID authorizing payment
     * @return GoodsReceiptNoteInterface
     * @throws \Nexus\Procurement\Exceptions\UnauthorizedApprovalException
     */
    public function authorizeGrnPayment(string $grnId, string $authorizerId): GoodsReceiptNoteInterface;
}
