<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\ConsentQueryInterface;
use Nexus\DataPrivacy\Contracts\ConsentPersistInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;
use Nexus\DataPrivacy\Exceptions\ConsentNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidConsentException;

/**
 * Manages consent lifecycle operations.
 */
final readonly class ConsentManager implements ConsentManagerInterface
{
    public function __construct(
        private ConsentQueryInterface $consentQuery,
        private ConsentPersistInterface $consentPersist,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function grantConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose,
        array $options = []
    ): Consent {
        // Check if consent already exists and is valid
        $existing = $this->consentQuery->findByDataSubjectAndPurpose(
            $dataSubjectId->getValue(),
            $purpose
        );

        if ($existing !== null && $existing->isValid()) {
            throw new InvalidConsentException(
                "Valid consent already exists for purpose: {$purpose->value}"
            );
        }

        $consent = Consent::grant(
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            version: $options['version'] ?? 1,
            expiresAt: $options['expiresAt'] ?? null,
            ipAddress: $options['ipAddress'] ?? null,
            userAgent: $options['userAgent'] ?? null
        );

        $consentId = $this->consentPersist->save($consent);

        $this->auditLogger?->logConsentGranted(
            $dataSubjectId->getValue(),
            $purpose->value,
            $consentId
        );

        return $consent;
    }

    public function withdrawConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose
    ): Consent {
        $consent = $this->consentQuery->findByDataSubjectAndPurpose(
            $dataSubjectId->getValue(),
            $purpose
        );

        if ($consent === null) {
            throw ConsentNotFoundException::forDataSubjectAndPurpose(
                $dataSubjectId->getValue(),
                $purpose->value
            );
        }

        if ($consent->status === ConsentStatus::WITHDRAWN) {
            throw new InvalidConsentException('Consent already withdrawn');
        }

        $withdrawnConsent = $consent->withdraw(new \DateTimeImmutable());
        $this->consentPersist->update($withdrawnConsent);

        $this->auditLogger?->logConsentWithdrawn(
            $dataSubjectId->getValue(),
            $purpose->value,
            $consent->id
        );

        return $withdrawnConsent;
    }

    public function hasValidConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose
    ): bool {
        return $this->consentQuery->hasValidConsent(
            $dataSubjectId->getValue(),
            $purpose
        );
    }

    public function getConsents(DataSubjectId $dataSubjectId): array
    {
        return $this->consentQuery->findByDataSubject($dataSubjectId->getValue());
    }

    public function getValidConsents(DataSubjectId $dataSubjectId): array
    {
        return $this->consentQuery->findValidConsents($dataSubjectId->getValue());
    }

    public function renewConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $newExpiresAt
    ): Consent {
        $consent = $this->consentQuery->findByDataSubjectAndPurpose(
            $dataSubjectId->getValue(),
            $purpose
        );

        if ($consent === null) {
            throw ConsentNotFoundException::forDataSubjectAndPurpose(
                $dataSubjectId->getValue(),
                $purpose->value
            );
        }

        // Create new consent with renewed expiry
        $renewedConsent = Consent::grant(
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            version: $consent->getVersion() + 1,
            expiresAt: $newExpiresAt,
            ipAddress: $consent->getIpAddress(),
            userAgent: $consent->getUserAgent()
        );

        // Mark old consent as expired
        $this->consentPersist->markExpired($consent->getId() ?? '');

        // Save new consent
        $this->consentPersist->save($renewedConsent);

        $this->auditLogger?->log(
            'consent',
            $consent->getId() ?? '',
            'renewed',
            "Consent renewed for purpose: {$purpose->value}",
            ['new_expiry' => $newExpiresAt->format('Y-m-d H:i:s')]
        );

        return $renewedConsent;
    }

    public function withdrawAllConsents(DataSubjectId $dataSubjectId): int
    {
        $consents = $this->consentQuery->findValidConsents($dataSubjectId->getValue());
        $count = 0;

        foreach ($consents as $consent) {
            if ($consent->getStatus() !== ConsentStatus::WITHDRAWN) {
                $withdrawnConsent = $consent->withdraw();
                $this->consentPersist->update($withdrawnConsent);
                $count++;

                $this->auditLogger?->logConsentWithdrawn(
                    $dataSubjectId->getValue(),
                    $consent->getPurpose()->value,
                    $consent->getId() ?? ''
                );
            }
        }

        return $count;
    }

    public function exportConsentRecords(DataSubjectId $dataSubjectId): array
    {
        $consents = $this->consentQuery->findByDataSubject($dataSubjectId->getValue());

        return array_map(
            fn(Consent $consent) => $consent->toArray(),
            $consents
        );
    }

    public function processExpiredConsents(): int
    {
        return $this->consentPersist->bulkMarkExpired();
    }
}
