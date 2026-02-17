<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * Default implementation of retention policy.
 *
 * Provides standard retention rules and integrates with legal hold repository.
 */
final readonly class DefaultRetentionPolicy implements RetentionPolicyInterface
{
    private const DEFAULT_RETENTION_YEARS = 5;
    private const TYPE_RETENTION_MAP = [
        DocumentType::CONTRACT->value => 7,
        DocumentType::INVOICE->value => 7,
        DocumentType::REPORT->value => 3,
        DocumentType::OTHER->value => 1,
    ];

    public function __construct(
        private LegalHoldRepositoryInterface $legalHoldRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionDays(string $documentType): int
    {
        return $this->getRetentionYears($documentType) * 365;
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionYears(string $documentType): int
    {
        return self::TYPE_RETENTION_MAP[$documentType] ?? self::DEFAULT_RETENTION_YEARS;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired(\DateTimeInterface $createdAt, string $documentType): bool
    {
        $expirationDate = $this->getExpirationDate($createdAt, $documentType);

        return new \DateTimeImmutable() >= $expirationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpirationDate(\DateTimeInterface $createdAt, string $documentType): \DateTimeImmutable
    {
        $years = $this->getRetentionYears($documentType);

        return \DateTimeImmutable::createFromInterface($createdAt)
            ->modify("+{$years} years");
    }

    /**
     * {@inheritdoc}
     */
    public function canPurge(string $documentId): bool
    {
        // 1. Must not have active legal hold
        if ($this->hasLegalHold($documentId)) {
            return false;
        }

        // 2. Implementation could add more checks here (e.g. regulatory status)
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLegalHold(string $documentId): bool
    {
        return $this->legalHoldRepository->hasActiveHold($documentId);
    }

    /**
     * {@inheritdoc}
     */
    public function applyLegalHold(
        string $documentId,
        string $reason,
        string $appliedBy,
        ?string $matterReference = null,
        ?\DateTimeInterface $expiresAt = null
    ): LegalHoldInterface {
        return $this->legalHoldRepository->create([
            'document_id' => $documentId,
            'reason' => $reason,
            'applied_by' => $appliedBy,
            'matter_reference' => $matterReference,
            'expires_at' => $expiresAt,
            'applied_at' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLegalHold(
        string $documentId,
        string $releasedBy,
        ?string $releaseReason = null
    ): LegalHoldInterface {
        $activeHolds = $this->legalHoldRepository->findActiveByDocumentId($documentId);

        foreach ($activeHolds as $hold) {
            // In a real implementation, we might want to release a specific hold ID.
            // For the policy interface, we provide a general release mechanism.
            // This is a simplification; production usually handles individual holds.
        }

        throw new \LogicException('Release logic should be handled by RetentionService or specific hold ID.');
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveLegalHolds(string $documentId): array
    {
        return $this->legalHoldRepository->findActiveByDocumentId($documentId);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegulatoryBasis(string $documentType): ?string
    {
        return match ($documentType) {
            DocumentType::CONTRACT->value => 'Standard Commercial Contract Law',
            DocumentType::INVOICE->value => 'Tax/Financial Compliance (7yr)',
            default => 'Standard Internal Policy',
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getDisposalMethod(string $documentType): string
    {
        return match ($documentType) {
            DocumentType::CONTRACT->value, DocumentType::INVOICE->value => 'SECURE_DELETE',
            default => 'DELETE',
        };
    }
}
