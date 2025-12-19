<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\ValueObjects\RecipientInfo;

/**
 * Contract for disbursement manager service.
 *
 * Core service for creating, approving, and processing disbursements.
 */
interface DisbursementManagerInterface
{
    /**
     * Create a new disbursement.
     *
     * @param string $tenantId Tenant ID
     * @param Money $amount Disbursement amount
     * @param RecipientInfo $recipient Recipient information
     * @param PaymentMethodType $methodType Payment method type for disbursement
     * @param string $createdBy ID of user creating the disbursement
     * @param array<string>|null $sourceDocumentIds Source document IDs
     * @param \DateTimeImmutable|null $scheduledDate Scheduled date for processing
     * @param array<string, mixed>|null $metadata Additional metadata
     * @return DisbursementInterface The created disbursement
     */
    public function create(
        string $tenantId,
        Money $amount,
        RecipientInfo $recipient,
        PaymentMethodType $methodType,
        string $createdBy,
        ?array $sourceDocumentIds = null,
        ?\DateTimeImmutable $scheduledDate = null,
        ?array $metadata = null,
    ): DisbursementInterface;

    /**
     * Get a disbursement by ID.
     */
    public function getById(string $id): DisbursementInterface;

    /**
     * Submit a draft disbursement for approval.
     *
     * @param string $disbursementId Disbursement ID
     * @return DisbursementInterface Updated disbursement
     */
    public function submitForApproval(string $disbursementId): DisbursementInterface;

    /**
     * Approve a disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param string $approvedBy ID of approving user
     * @param string|null $notes Approval notes
     * @return DisbursementInterface Updated disbursement
     */
    public function approve(
        string $disbursementId,
        string $approvedBy,
        ?string $notes = null,
    ): DisbursementInterface;

    /**
     * Reject a disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param string $rejectedBy ID of rejecting user
     * @param string $reason Rejection reason
     * @return DisbursementInterface Updated disbursement
     */
    public function reject(
        string $disbursementId,
        string $rejectedBy,
        string $reason,
    ): DisbursementInterface;

    /**
     * Process an approved disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @return DisbursementInterface Updated disbursement with payment transaction
     */
    public function process(string $disbursementId): DisbursementInterface;

    /**
     * Cancel a disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param string $cancelledBy ID of user cancelling
     * @param string $reason Cancellation reason
     * @return DisbursementInterface Updated disbursement
     */
    public function cancel(
        string $disbursementId,
        string $cancelledBy,
        string $reason,
    ): DisbursementInterface;

    /**
     * Link source documents to a disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param array<string> $documentIds Document IDs to link
     * @return DisbursementInterface Updated disbursement
     */
    public function linkSourceDocuments(
        string $disbursementId,
        array $documentIds,
    ): DisbursementInterface;

    /**
     * Schedule a disbursement for future processing.
     *
     * @param string $disbursementId Disbursement ID
     * @param \DateTimeImmutable $scheduledDate Scheduled processing date
     * @return DisbursementInterface Updated disbursement
     */
    public function schedule(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementInterface;

    /**
     * Get disbursements by status.
     *
     * @return array<DisbursementInterface>
     */
    public function findByStatus(DisbursementStatus $status): array;

    /**
     * Get disbursements pending approval.
     *
     * @param string $tenantId Tenant ID
     * @return array<DisbursementInterface>
     */
    public function getPendingApprovals(string $tenantId): array;

    /**
     * Get disbursements ready for processing.
     *
     * @param string $tenantId Tenant ID
     * @return array<DisbursementInterface>
     */
    public function getReadyForProcessing(string $tenantId): array;
}
