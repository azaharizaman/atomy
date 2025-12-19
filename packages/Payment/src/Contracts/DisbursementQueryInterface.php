<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\Enums\DisbursementStatus;

/**
 * Query interface for disbursements (CQRS - Read).
 */
interface DisbursementQueryInterface
{
    /**
     * Find a disbursement by its ID.
     */
    public function findById(string $id): ?DisbursementInterface;

    /**
     * Find a disbursement by its reference number.
     */
    public function findByReferenceNumber(string $referenceNumber): ?DisbursementInterface;

    /**
     * Find disbursements by status.
     *
     * @return array<DisbursementInterface>
     */
    public function findByStatus(DisbursementStatus $status): array;

    /**
     * Find disbursements by recipient ID.
     *
     * @return array<DisbursementInterface>
     */
    public function findByRecipientId(string $recipientId): array;

    /**
     * Find disbursements pending approval.
     *
     * @return array<DisbursementInterface>
     */
    public function findPendingApproval(): array;

    /**
     * Find disbursements ready for processing.
     *
     * @return array<DisbursementInterface>
     */
    public function findReadyForProcessing(): array;

    /**
     * Find disbursements scheduled for a specific date.
     *
     * @return array<DisbursementInterface>
     */
    public function findScheduledForDate(\DateTimeImmutable $date): array;

    /**
     * Find disbursements created by a user.
     *
     * @return array<DisbursementInterface>
     */
    public function findByCreatedBy(string $userId): array;

    /**
     * Find disbursements within a date range.
     *
     * @return array<DisbursementInterface>
     */
    public function findByDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array;

    /**
     * Find disbursements linked to a source document.
     *
     * @return array<DisbursementInterface>
     */
    public function findBySourceDocumentId(string $documentId): array;

    /**
     * Count disbursements by status.
     */
    public function countByStatus(DisbursementStatus $status): int;

    /**
     * Get total amount by status.
     */
    public function sumAmountByStatus(DisbursementStatus $status, string $currency): float;
}
