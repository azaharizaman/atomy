<?php

declare(strict_types=1);

namespace Nexus\Tax\Services;

use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
use Nexus\Tax\Exceptions\ExemptionCertificateExpiredException;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Psr\Log\LoggerInterface;

/**
 * Exemption Manager Service
 * 
 * Validates and manages tax exemption certificates.
 * 
 * NOTE: This is an interface definition only. Application layer must
 * provide concrete implementation with database persistence.
 * 
 * This reference implementation shows validation logic only.
 */
final readonly class ExemptionManager implements TaxExemptionManagerInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function isValid(
        ExemptionCertificate $certificate,
        \DateTimeInterface $useDate,
        ?string $jurisdictionCode = null
    ): bool {
        $this->logger?->info('Validating exemption certificate', [
            'certificate_id' => $certificate->certificateId,
            'use_date' => $useDate->format('Y-m-d'),
        ]);

        // Check if certificate is valid on use date
        if (!$certificate->isValidOn($useDate)) {
            $this->logger?->warning('Certificate expired or not yet valid', [
                'certificate_id' => $certificate->certificateId,
                'use_date' => $useDate->format('Y-m-d'),
                'issue_date' => $certificate->issueDate->format('Y-m-d'),
                'expiration_date' => $certificate->expirationDate?->format('Y-m-d'),
            ]);

            throw new ExemptionCertificateExpiredException(
                $certificate->certificateId,
                $certificate->expirationDate ?? new \DateTimeImmutable('9999-12-31'),
                $useDate instanceof \DateTimeImmutable
                    ? $useDate
                    : \DateTimeImmutable::createFromInterface($useDate)
            );
        }

        // Check jurisdiction match (if specified)
        if ($jurisdictionCode !== null && $certificate->jurisdictionCode !== null) {
            if ($certificate->jurisdictionCode !== $jurisdictionCode) {
                $this->logger?->warning('Certificate jurisdiction mismatch', [
                    'certificate_id' => $certificate->certificateId,
                    'certificate_jurisdiction' => $certificate->jurisdictionCode,
                    'requested_jurisdiction' => $jurisdictionCode,
                ]);

                return false;
            }
        }

        $this->logger?->info('Certificate validated successfully', [
            'certificate_id' => $certificate->certificateId,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function findValidCertificate(
        string $customerId,
        \DateTimeInterface $useDate,
        ?string $jurisdictionCode = null
    ): ?ExemptionCertificate {
        // NOTE: Application layer must implement database query
        // This is interface definition only
        throw new \BadMethodCallException(
            'findValidCertificate() must be implemented by application layer using repository pattern'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCertificatesForCustomer(string $customerId, bool $onlyActive = true): array
    {
        // NOTE: Application layer must implement database query
        throw new \BadMethodCallException(
            'getCertificatesForCustomer() must be implemented by application layer using repository pattern'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasValidExemption(
        string $customerId,
        \DateTimeInterface $date,
        ?string $jurisdictionCode = null
    ): bool {
        try {
            $certificate = $this->findValidCertificate($customerId, $date, $jurisdictionCode);
            return $certificate !== null;
        } catch (\BadMethodCallException) {
            // Application layer not implemented yet
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiringSoon(int $daysAhead = 30, ?\DateTimeInterface $fromDate = null): array
    {
        // NOTE: Application layer must implement database query
        throw new \BadMethodCallException(
            'getExpiringSoon() must be implemented by application layer using repository pattern'
        );
    }
}
