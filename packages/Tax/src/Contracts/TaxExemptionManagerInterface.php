<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\ExemptionCertificate;

/**
 * Tax Exemption Manager Interface
 * 
 * Manages validation and retrieval of tax exemption certificates.
 * 
 * Application layer implements this with:
 * - Database certificate storage
 * - PDF storage (Nexus\Storage)
 * - Expiration checks
 * - Jurisdiction validation
 */
interface TaxExemptionManagerInterface
{
    /**
     * Validate exemption certificate is valid for use
     * 
     * Checks:
     * - Certificate is not expired
     * - Certificate is valid in jurisdiction
     * - Certificate is valid for customer
     * 
     * @param ExemptionCertificate $certificate Certificate to validate
     * @param \DateTimeInterface $useDate Date certificate will be used
     * @param string|null $jurisdictionCode Optional jurisdiction to validate against
     * 
     * @return bool True if valid
     * 
     * @throws \Nexus\Tax\Exceptions\ExemptionCertificateExpiredException If expired
     */
    public function isValid(
        ExemptionCertificate $certificate,
        \DateTimeInterface $useDate,
        ?string $jurisdictionCode = null
    ): bool;

    /**
     * Find valid exemption certificate for customer
     * 
     * Returns the most recent valid certificate for customer.
     * 
     * @param string $customerId Customer ID
     * @param \DateTimeInterface $useDate Date certificate must be valid
     * @param string|null $jurisdictionCode Optional jurisdiction filter
     * 
     * @return ExemptionCertificate|null Certificate or null if none found
     */
    public function findValidCertificate(
        string $customerId,
        \DateTimeInterface $useDate,
        ?string $jurisdictionCode = null
    ): ?ExemptionCertificate;

    /**
     * Get all certificates for customer
     * 
     * @param string $customerId Customer ID
     * @param bool $onlyActive If true, only return active certificates
     * 
     * @return array<ExemptionCertificate>
     */
    public function getCertificatesForCustomer(string $customerId, bool $onlyActive = true): array;

    /**
     * Check if customer has valid exemption
     * 
     * @param string $customerId Customer ID
     * @param \DateTimeInterface $date Date to check
     * @param string|null $jurisdictionCode Optional jurisdiction
     * 
     * @return bool True if customer has valid exemption
     */
    public function hasValidExemption(
        string $customerId,
        \DateTimeInterface $date,
        ?string $jurisdictionCode = null
    ): bool;

    /**
     * Get certificates expiring soon
     * 
     * Returns certificates expiring within specified days.
     * Useful for renewal reminders.
     * 
     * @param int $daysAhead Number of days to look ahead
     * @param \DateTimeInterface|null $fromDate Starting date (default: today)
     * 
     * @return array<ExemptionCertificate>
     */
    public function getExpiringSoon(int $daysAhead = 30, ?\DateTimeInterface $fromDate = null): array;
}
