<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

use Nexus\ProcurementOperations\Enums\RetentionCategory;

/**
 * DTO representing a retention policy configuration.
 */
final readonly class RetentionPolicyData
{
    /**
     * @param string $policyId Policy identifier
     * @param RetentionCategory $category Document category
     * @param int $retentionYears Retention period in years
     * @param string $disposalMethod Disposal method when period expires
     * @param string $regulatoryBasis Legal/regulatory basis
     * @param bool $legalHoldEnabled Whether legal hold is possible
     * @param bool $autoArchiveEnabled Whether auto-archive is enabled
     * @param int $archiveAfterDays Days after which to archive
     * @param array $metadata Additional policy metadata
     */
    public function __construct(
        public string $policyId,
        public RetentionCategory $category,
        public int $retentionYears,
        public string $disposalMethod,
        public string $regulatoryBasis,
        public bool $legalHoldEnabled = true,
        public bool $autoArchiveEnabled = false,
        public int $archiveAfterDays = 365,
        public array $metadata = [],
    ) {}

    /**
     * Calculate expiration date from creation date.
     */
    public function calculateExpirationDate(\DateTimeImmutable $createdAt): \DateTimeImmutable
    {
        return $createdAt->modify("+{$this->retentionYears} years");
    }

    /**
     * Check if document created at date is still within retention.
     */
    public function isWithinRetention(\DateTimeImmutable $createdAt, ?\DateTimeImmutable $asOfDate = null): bool
    {
        $asOfDate ??= new \DateTimeImmutable();
        $expirationDate = $this->calculateExpirationDate($createdAt);
        return $asOfDate < $expirationDate;
    }

    /**
     * Get remaining retention days.
     */
    public function getRemainingDays(\DateTimeImmutable $createdAt, ?\DateTimeImmutable $asOfDate = null): int
    {
        $asOfDate ??= new \DateTimeImmutable();
        $expirationDate = $this->calculateExpirationDate($createdAt);
        $diff = $asOfDate->diff($expirationDate);
        return $diff->invert ? 0 : $diff->days;
    }

    /**
     * Check if document should be archived.
     */
    public function shouldArchive(\DateTimeImmutable $createdAt, ?\DateTimeImmutable $asOfDate = null): bool
    {
        if (!$this->autoArchiveEnabled) {
            return false;
        }

        $asOfDate ??= new \DateTimeImmutable();
        $archiveDate = $createdAt->modify("+{$this->archiveAfterDays} days");
        return $asOfDate >= $archiveDate;
    }

    /**
     * Create from retention category with defaults.
     */
    public static function fromCategory(RetentionCategory $category): self
    {
        return new self(
            policyId: 'POL-' . strtoupper($category->value),
            category: $category,
            retentionYears: $category->getRetentionYears(),
            disposalMethod: $category->getDisposalMethod(),
            regulatoryBasis: $category->getRegulatoryBasis(),
            legalHoldEnabled: $category->requiresLegalHoldCheck(),
            autoArchiveEnabled: false,
            archiveAfterDays: 365,
            metadata: [
                'sox_applicable' => $category->isSubjectToSox(),
            ],
        );
    }
}
