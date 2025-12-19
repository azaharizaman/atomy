<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\ValueObjects\RecipientInfo;

/**
 * Contract for disbursement entities.
 *
 * Represents an outbound payment/payout to a vendor, employee, or other recipient.
 */
interface DisbursementInterface
{
    /**
     * Get the unique disbursement ID.
     */
    public function getId(): string;

    /**
     * Get the tenant ID this disbursement belongs to.
     */
    public function getTenantId(): string;

    /**
     * Get the disbursement reference number.
     */
    public function getReferenceNumber(): string;

    /**
     * Get the current status.
     */
    public function getStatus(): DisbursementStatus;

    /**
     * Get the disbursement amount.
     */
    public function getAmount(): Money;

    /**
     * Get the recipient information.
     */
    public function getRecipient(): RecipientInfo;

    /**
     * Get the payment method type for disbursement.
     */
    public function getMethodType(): PaymentMethodType;

    /**
     * Get the source account ID for the disbursement.
     */
    public function getSourceAccountId(): ?string;

    /**
     * Get the description/purpose.
     */
    public function getDescription(): ?string;

    /**
     * Get the linked source documents (invoice IDs, bill IDs, etc.).
     *
     * @return array<string>
     */
    public function getSourceDocumentIds(): array;

    /**
     * Get the ID of the user who created this disbursement.
     */
    public function getCreatedBy(): string;

    /**
     * Get the ID of the user who approved this disbursement.
     */
    public function getApprovedBy(): ?string;

    /**
     * Get the approval notes.
     */
    public function getApprovalNotes(): ?string;

    /**
     * Get the rejection reason if rejected.
     */
    public function getRejectionReason(): ?string;

    /**
     * Get the linked payment transaction ID once executed.
     */
    public function getPaymentTransactionId(): ?string;

    /**
     * Get the scheduled disbursement date.
     */
    public function getScheduledDate(): ?\DateTimeImmutable;

    /**
     * Get the date/time when disbursement was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the date/time when disbursement was approved.
     */
    public function getApprovedAt(): ?\DateTimeImmutable;

    /**
     * Get the date/time when disbursement was completed.
     */
    public function getCompletedAt(): ?\DateTimeImmutable;

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if the disbursement can transition to the given status.
     */
    public function canTransitionTo(DisbursementStatus $status): bool;

    /**
     * Check if the disbursement is in a terminal state.
     */
    public function isTerminal(): bool;

    /**
     * Check if the disbursement requires approval.
     */
    public function requiresApproval(): bool;

    /**
     * Check if the disbursement is ready for processing.
     */
    public function isReadyForProcessing(): bool;

    // -------------------------------------------------------------------------
    // Mutation Methods
    // -------------------------------------------------------------------------

    /**
     * Submit disbursement for approval.
     *
     * @throws InvalidDisbursementStatusException If not in DRAFT status
     */
    public function submitForApproval(): void;

    /**
     * Approve the disbursement.
     *
     * @param string $approvedBy User ID who approved
     * @param string|null $notes Approval notes
     *
     * @throws InvalidDisbursementStatusException If not in PENDING_APPROVAL status
     */
    public function approve(string $approvedBy, ?string $notes = null): void;

    /**
     * Reject the disbursement.
     *
     * @param string $rejectedBy User ID who rejected
     * @param string $reason Rejection reason
     *
     * @throws InvalidDisbursementStatusException If not in PENDING_APPROVAL status
     */
    public function reject(string $rejectedBy, string $reason): void;

    /**
     * Mark disbursement as processing.
     *
     * @throws InvalidDisbursementStatusException If not in APPROVED status
     */
    public function markAsProcessing(): void;

    /**
     * Mark disbursement as completed.
     *
     * @param string $paymentTransactionId Payment transaction ID
     *
     * @throws InvalidDisbursementStatusException If not in PROCESSING status
     */
    public function markAsCompleted(string $paymentTransactionId): void;

    /**
     * Mark disbursement as failed.
     *
     * @param string $failureCode Failure code
     * @param string $failureMessage Failure message
     *
     * @throws InvalidDisbursementStatusException If not in PROCESSING status
     */
    public function markAsFailed(string $failureCode, string $failureMessage): void;

    /**
     * Cancel the disbursement.
     *
     * @param string|null $reason Cancellation reason
     *
     * @throws InvalidDisbursementStatusException If in terminal or processing status
     */
    public function cancel(?string $reason = null): void;

    /**
     * Schedule the disbursement for a future date.
     *
     * @param \DateTimeImmutable $scheduledDate Scheduled payment date
     *
     * @throws InvalidDisbursementStatusException If in terminal status
     */
    public function schedule(\DateTimeImmutable $scheduledDate): void;

    /**
     * Link source documents to the disbursement.
     *
     * @param array<string> $documentIds Document IDs to link
     */
    public function linkSourceDocuments(array $documentIds): void;
}
