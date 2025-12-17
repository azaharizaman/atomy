<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\Enums\VerificationStatus;
use Nexus\KycVerification\ValueObjects\DocumentVerification;
use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\ValueObjects\VerificationResult;

/**
 * Main interface for KYC verification operations.
 */
interface KycVerificationManagerInterface
{
    /**
     * Initiate KYC verification for a party
     * 
     * @param array<string, mixed> $partyData Additional party data
     */
    public function initiateVerification(
        string $partyId,
        DueDiligenceLevel $dueDiligenceLevel = DueDiligenceLevel::STANDARD,
        array $partyData = []
    ): VerificationResult;

    /**
     * Get KYC profile for a party
     */
    public function getProfile(string $partyId): ?KycProfile;

    /**
     * Update verification status
     */
    public function updateStatus(
        string $partyId,
        VerificationStatus $newStatus,
        ?string $reason = null,
        ?string $updatedBy = null
    ): VerificationResult;

    /**
     * Add document verification to profile
     */
    public function addDocumentVerification(
        string $partyId,
        DocumentVerification $documentVerification
    ): VerificationResult;

    /**
     * Complete verification process
     * 
     * @param array<string, mixed> $additionalData Any additional data to store
     */
    public function completeVerification(
        string $partyId,
        ?string $verifiedBy = null,
        array $additionalData = []
    ): VerificationResult;

    /**
     * Reject verification
     * 
     * @param array<string> $reasons Rejection reasons
     */
    public function rejectVerification(
        string $partyId,
        array $reasons,
        ?string $rejectedBy = null
    ): VerificationResult;

    /**
     * Check if party is verified
     */
    public function isVerified(string $partyId): bool;

    /**
     * Check if party can transact (active status)
     */
    public function canTransact(string $partyId): bool;

    /**
     * Get verification status
     */
    public function getStatus(string $partyId): ?VerificationStatus;

    /**
     * Trigger re-verification for a party
     */
    public function triggerReverification(
        string $partyId,
        string $reason,
        ?string $triggeredBy = null
    ): VerificationResult;

    /**
     * Calculate verification score for a party
     */
    public function calculateVerificationScore(string $partyId): int;

    /**
     * Get parties pending verification
     * 
     * @return array<string> Party IDs
     */
    public function getPendingVerifications(?int $limit = null): array;

    /**
     * Get parties with expiring verification
     * 
     * @return array<string> Party IDs
     */
    public function getExpiringVerifications(int $withinDays = 30): array;
}
