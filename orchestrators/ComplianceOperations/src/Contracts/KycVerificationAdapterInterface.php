<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Interface for KYC verification adapter.
 *
 * This interface defines the contract for the orchestrator's KYC verification
 * needs. Adapters implement this interface using the KycVerification package.
 *
 * Following Interface Segregation from ARCHITECTURE.md:
 * Orchestrators define their own interfaces, not depending on atomic package interfaces.
 */
interface KycVerificationAdapterInterface
{
    /**
     * Initiate KYC verification for a party.
     *
     * @param string $partyId Party identifier
     * @param string $dueDiligenceLevel Due diligence level (simplified, standard, enhanced)
     * @param array<string, mixed> $partyData Additional party data
     * @return array<string, mixed> Verification result
     */
    public function initiateVerification(
        string $partyId,
        string $dueDiligenceLevel = 'standard',
        array $partyData = []
    ): array;

    /**
     * Get KYC profile for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Profile data or null if not found
     */
    public function getProfile(string $partyId): ?array;

    /**
     * Check if party is verified.
     *
     * @param string $partyId Party identifier
     */
    public function isVerified(string $partyId): bool;

    /**
     * Check if party can transact.
     *
     * @param string $partyId Party identifier
     */
    public function canTransact(string $partyId): bool;

    /**
     * Get verification status.
     *
     * @param string $partyId Party identifier
     * @return string|null Status value or null if not found
     */
    public function getStatus(string $partyId): ?string;

    /**
     * Get risk level for a party.
     *
     * @param string $partyId Party identifier
     * @return string|null Risk level value or null if not assessed
     */
    public function getRiskLevel(string $partyId): ?string;

    /**
     * Check if party requires enhanced due diligence.
     *
     * @param string $partyId Party identifier
     */
    public function requiresEnhancedDueDiligence(string $partyId): bool;

    /**
     * Update verification status.
     *
     * @param string $partyId Party identifier
     * @param string $status New status
     * @param string|null $reason Reason for status change
     * @param string|null $updatedBy User who updated
     * @return array<string, mixed> Verification result
     */
    public function updateStatus(
        string $partyId,
        string $status,
        ?string $reason = null,
        ?string $updatedBy = null
    ): array;

    /**
     * Complete verification.
     *
     * @param string $partyId Party identifier
     * @param string|null $verifiedBy User who verified
     * @param array<string, mixed> $additionalData Additional data
     * @return array<string, mixed> Verification result
     */
    public function completeVerification(
        string $partyId,
        ?string $verifiedBy = null,
        array $additionalData = []
    ): array;

    /**
     * Reject verification.
     *
     * @param string $partyId Party identifier
     * @param array<string> $reasons Rejection reasons
     * @param string|null $rejectedBy User who rejected
     * @return array<string, mixed> Verification result
     */
    public function rejectVerification(
        string $partyId,
        array $reasons,
        ?string $rejectedBy = null
    ): array;

    /**
     * Trigger re-verification.
     *
     * @param string $partyId Party identifier
     * @param string $reason Reason for re-verification
     * @param string|null $triggeredBy User who triggered
     * @return array<string, mixed> Verification result
     */
    public function triggerReverification(
        string $partyId,
        string $reason,
        ?string $triggeredBy = null
    ): array;

    /**
     * Get verification score.
     *
     * @param string $partyId Party identifier
     * @return int Verification score (0-100)
     */
    public function getVerificationScore(string $partyId): int;
}
