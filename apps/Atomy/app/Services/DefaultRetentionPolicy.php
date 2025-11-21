<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Document\Contracts\RetentionPolicyInterface;

/**
 * Default retention policy implementation (stub).
 *
 * Provides basic retention logic until Nexus\Compliance is integrated.
 */
final class DefaultRetentionPolicy implements RetentionPolicyInterface
{
    /**
     * Default retention periods by document type (in days).
     */
    private const RETENTION_PERIODS = [
        'contract' => 2555, // ~7 years
        'invoice' => 2555,  // ~7 years
        'report' => 1825,   // ~5 years
        'image' => 365,     // 1 year
        'spreadsheet' => 1825, // ~5 years
        'presentation' => 365,  // 1 year
        'pdf' => 1825,      // ~5 years
        'other' => 365,     // 1 year (default)
    ];

    public function getRetentionDays(string $documentType): int
    {
        return self::RETENTION_PERIODS[$documentType] ?? self::RETENTION_PERIODS['other'];
    }

    public function isExpired(\DateTimeInterface $createdAt, string $documentType): bool
    {
        $retentionDays = $this->getRetentionDays($documentType);
        $expiryDate = (clone $createdAt)->modify("+{$retentionDays} days");

        return new \DateTimeImmutable() > $expiryDate;
    }

    public function canPurge(string $documentId): bool
    {
        // Check for legal hold
        if ($this->hasLegalHold($documentId)) {
            return false;
        }

        // Additional checks can be added here
        return true;
    }

    public function hasLegalHold(string $documentId): bool
    {
        // TODO: Check legal_holds table when implemented
        return false;
    }

    public function applyLegalHold(string $documentId, string $reason, string $appliedBy): void
    {
        // TODO: Insert into legal_holds table
    }

    public function releaseLegalHold(string $documentId, string $releasedBy): void
    {
        // TODO: Remove from legal_holds table
    }
}
