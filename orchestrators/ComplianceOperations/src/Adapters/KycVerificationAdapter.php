<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Adapters;

use Nexus\KycVerification\Contracts\KycVerificationManagerInterface;
use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\RiskAssessorInterface;
use Nexus\KycVerification\Enums\VerificationStatus;
use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\ValueObjects\VerificationResult;
use Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface;

/**
 * Adapter for KycVerification package interface.
 *
 * Adapts the KycVerification package to the ComplianceOperations orchestrator's
 * interface requirements. This adapter implements the orchestrator's own contract
 * and delegates to the atomic package's interfaces.
 *
 * Following the Interface Segregation principle from ARCHITECTURE.md:
 * Orchestrators define their own interfaces and adapters implement them using
 * atomic package interfaces.
 */
final readonly class KycVerificationAdapter implements KycVerificationAdapterInterface
{
    public function __construct(
        private KycVerificationManagerInterface $verificationManager,
        private KycProfileQueryInterface $profileQuery,
        private RiskAssessorInterface $riskAssessor,
    ) {}

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
    ): array {
        $level = DueDiligenceLevel::tryFrom($dueDiligenceLevel) ?? DueDiligenceLevel::STANDARD;
        
        $result = $this->verificationManager->initiateVerification(
            $partyId,
            $level,
            $partyData
        );

        return $this->buildVerificationResult($result);
    }

    /**
     * Get KYC profile for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Profile data or null if not found
     */
    public function getProfile(string $partyId): ?array
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        
        if ($profile === null) {
            return null;
        }

        return $this->buildProfileData($profile);
    }

    /**
     * Check if party is verified.
     *
     * @param string $partyId Party identifier
     */
    public function isVerified(string $partyId): bool
    {
        return $this->verificationManager->isVerified($partyId);
    }

    /**
     * Check if party can transact.
     *
     * @param string $partyId Party identifier
     */
    public function canTransact(string $partyId): bool
    {
        return $this->verificationManager->canTransact($partyId);
    }

    /**
     * Get verification status.
     *
     * @param string $partyId Party identifier
     * @return string|null Status value or null if not found
     */
    public function getStatus(string $partyId): ?string
    {
        $status = $this->verificationManager->getStatus($partyId);
        return $status?->value;
    }

    /**
     * Get risk level for a party.
     *
     * @param string $partyId Party identifier
     * @return string|null Risk level value or null if not assessed
     */
    public function getRiskLevel(string $partyId): ?string
    {
        $riskLevel = $this->riskAssessor->getRiskLevel($partyId);
        return $riskLevel?->value;
    }

    /**
     * Check if party requires enhanced due diligence.
     *
     * @param string $partyId Party identifier
     */
    public function requiresEnhancedDueDiligence(string $partyId): bool
    {
        $riskLevel = $this->riskAssessor->getRiskLevel($partyId);
        
        if ($riskLevel === null) {
            return false;
        }

        return in_array($riskLevel, [RiskLevel::HIGH, RiskLevel::VERY_HIGH, RiskLevel::PROHIBITED], true);
    }

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
    ): array {
        $newStatus = VerificationStatus::tryFrom($status);
        
        if ($newStatus === null) {
            throw new \InvalidArgumentException("Invalid verification status: {$status}");
        }

        $result = $this->verificationManager->updateStatus(
            $partyId,
            $newStatus,
            $reason,
            $updatedBy
        );

        return $this->buildVerificationResult($result);
    }

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
    ): array {
        $result = $this->verificationManager->completeVerification(
            $partyId,
            $verifiedBy,
            $additionalData
        );

        return $this->buildVerificationResult($result);
    }

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
    ): array {
        $result = $this->verificationManager->rejectVerification(
            $partyId,
            $reasons,
            $rejectedBy
        );

        return $this->buildVerificationResult($result);
    }

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
    ): array {
        $result = $this->verificationManager->triggerReverification(
            $partyId,
            $reason,
            $triggeredBy
        );

        return $this->buildVerificationResult($result);
    }

    /**
     * Get verification score.
     *
     * @param string $partyId Party identifier
     * @return int Verification score (0-100)
     */
    public function getVerificationScore(string $partyId): int
    {
        return $this->verificationManager->calculateVerificationScore($partyId);
    }

    /**
     * Build verification result array.
     *
     * @return array<string, mixed>
     */
    private function buildVerificationResult(VerificationResult $result): array
    {
        return [
            'success' => $result->isSuccess(),
            'partyId' => $result->partyId,
            'status' => $result->status->value,
            'message' => $result->getPrimaryError(),
            'errors' => $result->errors,
            'timestamp' => $result->verifiedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build profile data array.
     *
     * @return array<string, mixed>
     */
    private function buildProfileData(KycProfile $profile): array
    {
        return [
            'partyId' => $profile->partyId,
            'partyType' => $profile->partyType->value,
            'status' => $profile->status->value,
            'dueDiligenceLevel' => $profile->dueDiligenceLevel->value,
            'riskLevel' => $profile->riskAssessment->riskLevel->value,
            'riskScore' => $profile->riskAssessment->riskScore,
            'verificationScore' => $profile->verificationScore,
            'isVerified' => $profile->isVerified(),
            'isActive' => $profile->isActive(),
            'isExpired' => $profile->isExpired(),
            'requiresEdd' => $profile->requiresEdd(),
            'verifiedAt' => $profile->verifiedAt?->format('Y-m-d H:i:s'),
            'expiresAt' => $profile->expiresAt?->format('Y-m-d'),
            'nextReviewDate' => $profile->nextReviewDate?->format('Y-m-d'),
            'createdAt' => $profile->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $profile->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
