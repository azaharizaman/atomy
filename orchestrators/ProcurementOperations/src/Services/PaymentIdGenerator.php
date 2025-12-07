<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

/**
 * Service for generating payment-related IDs.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service handles ID generation logic
 * - Coordinator delegates to this service instead of doing it inline
 */
final readonly class PaymentIdGenerator
{
    /**
     * Generate a unique payment batch ID.
     */
    public function generateBatchId(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        return 'PAY-BATCH-' . $now->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * Generate a unique payment ID.
     *
     * Note: Payment ID does not include timestamp for brevity.
     */
    public function generatePaymentId(): string
    {
        return 'PAY-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
    }

    /**
     * Generate a payment reference number.
     */
    public function generatePaymentReference(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        return 'REF-' . $now->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /**
     * Generate a journal entry ID.
     */
    public function generateJournalEntryId(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        return 'JE-' . $now->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
