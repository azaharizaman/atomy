<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;

/**
 * Service for generating payment-related IDs.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service handles ID generation logic
 * - Coordinator delegates to this service instead of doing it inline
 * 
 * Uses SecureIdGeneratorInterface (backed by Nexus\Crypto) when available
 * for FIPS-compliant cryptographically secure random generation.
 */
final readonly class PaymentIdGenerator
{
    public function __construct(
        private ?SecureIdGeneratorInterface $secureIdGenerator = null,
    ) {}

    /**
     * Generate a unique payment batch ID.
     */
    public function generateBatchId(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        $random = $this->generateRandomHex(4);

        return 'PAY-BATCH-' . $now->format('Ymd') . '-' . strtoupper(substr($random, 0, 8));
    }

    /**
     * Generate a unique payment ID.
     *
     * Note: Payment ID does not include timestamp for brevity.
     */
    public function generatePaymentId(): string
    {
        $random = $this->generateRandomHex(8);

        return 'PAY-' . strtoupper(substr($random, 0, 16));
    }

    /**
     * Generate a payment reference number.
     */
    public function generatePaymentReference(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        $random = $this->generateRandomHex(3);

        return 'REF-' . $now->format('YmdHis') . '-' . strtoupper(substr($random, 0, 6));
    }

    /**
     * Generate a journal entry ID.
     */
    public function generateJournalEntryId(?\DateTimeImmutable $now = null): string
    {
        $now = $now ?? new \DateTimeImmutable();
        $random = $this->generateRandomHex(4);

        return 'JE-' . $now->format('Ymd') . '-' . strtoupper(substr($random, 0, 8));
    }

    /**
     * Generate random hex string using SecureIdGenerator when available.
     */
    private function generateRandomHex(int $byteLength): string
    {
        if ($this->secureIdGenerator !== null) {
            return $this->secureIdGenerator->randomHex($byteLength);
        }

        // Fallback for backward compatibility
        return bin2hex(random_bytes($byteLength));
    }
}
