<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\KycVerificationManagerInterface;
use Nexus\KycVerification\Contracts\RiskAssessorInterface;
use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\ValueObjects\RiskAssessment;
use Nexus\ComplianceOperations\DTOs\Kyc\KycVerificationContext;
use Nexus\ComplianceOperations\DTOs\Kyc\KycSummaryData;
use Nexus\ComplianceOperations\Exceptions\KycDataException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for KYC verification data aggregation.
 *
 * Aggregates KYC data from the KycVerification package to provide
 * comprehensive context for compliance workflows including:
 * - Verification status and history
 * - Risk assessments and factors
 * - Document verification status
 * - Review schedules and triggers
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class KycDataProvider
{
    public function __construct(
        private KycProfileQueryInterface $profileQuery,
        private KycVerificationManagerInterface $verificationManager,
        private RiskAssessorInterface $riskAssessor,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive KYC context for a party.
     *
     * @param string $tenantId Tenant context
     * @param string $partyId Party identifier
     * @throws KycDataException If data cannot be retrieved
     */
    public function getKycContext(string $tenantId, string $partyId): KycVerificationContext
    {
        $this->logger->info('Fetching KYC context', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
        ]);

        $profile = $this->profileQuery->findByPartyId($partyId);

        if ($profile === null) {
            throw KycDataException::profileNotFound($partyId);
        }

        $riskAssessment = $this->riskAssessor->getCurrentAssessment($partyId);
        $verificationStatus = $this->verificationManager->getStatus($partyId);
        $verificationScore = $this->verificationManager->calculateVerificationScore($partyId);
        $canTransact = $this->verificationManager->canTransact($partyId);

        return new KycVerificationContext(
            tenantId: $tenantId,
            partyId: $partyId,
            profile: $this->buildProfileData($profile),
            verificationStatus: $verificationStatus?->value ?? 'unknown',
            verificationScore: $verificationScore,
            riskAssessment: $riskAssessment !== null ? $this->buildRiskData($riskAssessment) : null,
            canTransact: $canTransact,
            documents: $this->buildDocumentsData($profile),
            reviewSchedule: $this->buildReviewScheduleData($profile),
            verificationHistory: $this->buildVerificationHistory($profile),
            createdAt: $profile->createdAt,
            updatedAt: $profile->updatedAt,
        );
    }

    /**
     * Get KYC summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     */
    public function getKycSummary(string $tenantId): KycSummaryData
    {
        $this->logger->info('Fetching KYC summary', [
            'tenant_id' => $tenantId,
        ]);

        $statusCounts = $this->profileQuery->countByStatus();
        $pendingProfiles = $this->profileQuery->findPending(limit: 100);
        $highRiskProfiles = $this->profileQuery->findHighRisk();
        $expiringProfiles = $this->profileQuery->findExpiring(withinDays: 30);
        $needingReview = $this->profileQuery->findNeedingReview();

        return new KycSummaryData(
            tenantId: $tenantId,
            totalProfiles: array_sum($statusCounts),
            statusCounts: $statusCounts,
            pendingCount: count($pendingProfiles),
            highRiskCount: count($highRiskProfiles),
            expiringCount: count($expiringProfiles),
            needingReviewCount: count($needingReview),
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get parties pending KYC verification.
     *
     * @param string $tenantId Tenant context
     * @param int|null $limit Maximum results
     * @return array<KycVerificationContext>
     */
    public function getPendingVerifications(string $tenantId, ?int $limit = null): array
    {
        $this->logger->info('Fetching pending KYC verifications', [
            'tenant_id' => $tenantId,
            'limit' => $limit,
        ]);

        $profiles = $this->profileQuery->findPending($limit);
        $contexts = [];

        foreach ($profiles as $profile) {
            try {
                $contexts[] = $this->getKycContext($tenantId, $profile->partyId);
            } catch (KycDataException $e) {
                $this->logger->warning('Failed to get KYC context for profile', [
                    'party_id' => $profile->partyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $contexts;
    }

    /**
     * Get parties with high-risk KYC status.
     *
     * @param string $tenantId Tenant context
     * @return array<KycVerificationContext>
     */
    public function getHighRiskParties(string $tenantId): array
    {
        $this->logger->info('Fetching high-risk KYC parties', [
            'tenant_id' => $tenantId,
        ]);

        $profiles = $this->profileQuery->findHighRisk();
        $contexts = [];

        foreach ($profiles as $profile) {
            try {
                $contexts[] = $this->getKycContext($tenantId, $profile->partyId);
            } catch (KycDataException $e) {
                $this->logger->warning('Failed to get KYC context for high-risk profile', [
                    'party_id' => $profile->partyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $contexts;
    }

    /**
     * Get parties with KYC expiring within specified days.
     *
     * @param string $tenantId Tenant context
     * @param int $withinDays Days threshold
     * @return array<KycVerificationContext>
     */
    public function getExpiringVerifications(string $tenantId, int $withinDays = 30): array
    {
        $this->logger->info('Fetching expiring KYC verifications', [
            'tenant_id' => $tenantId,
            'within_days' => $withinDays,
        ]);

        $profiles = $this->profileQuery->findExpiring($withinDays);
        $contexts = [];

        foreach ($profiles as $profile) {
            try {
                $contexts[] = $this->getKycContext($tenantId, $profile->partyId);
            } catch (KycDataException $e) {
                $this->logger->warning('Failed to get KYC context for expiring profile', [
                    'party_id' => $profile->partyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $contexts;
    }

    /**
     * Check if party has valid KYC verification.
     *
     * @param string $partyId Party identifier
     */
    public function hasValidVerification(string $partyId): bool
    {
        return $this->verificationManager->isVerified($partyId);
    }

    /**
     * Get verification status for a party.
     *
     * @param string $partyId Party identifier
     * @return string|null Status value or null if not found
     */
    public function getVerificationStatus(string $partyId): ?string
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
     * Build profile data array from KycProfile.
     *
     * @return array<string, mixed>
     */
    private function buildProfileData(KycProfile $profile): array
    {
        return [
            'partyId' => $profile->partyId,
            'partyType' => $profile->partyType->value,
            'dueDiligenceLevel' => $profile->dueDiligenceLevel->value,
            'primaryCountry' => $profile->addressVerification?->country ?? null,
            'associatedCountries' => [],
            'industryCode' => $profile->additionalData['industryCode'] ?? null,
            'verificationExpiry' => $profile->expiresAt?->format('Y-m-d'),
            'lastReviewDate' => null,
            'nextReviewDate' => $profile->nextReviewDate?->format('Y-m-d'),
        ];
    }

    /**
     * Build risk assessment data array.
     *
     * @return array<string, mixed>
     */
    private function buildRiskData(RiskAssessment $assessment): array
    {
        return [
            'riskLevel' => $assessment->riskLevel->value,
            'riskScore' => $assessment->riskScore,
            'riskFactors' => array_map(
                fn($factor) => [
                    'code' => $factor->code,
                    'category' => $factor->category,
                    'score' => $factor->score,
                    'description' => $factor->description,
                    'details' => $factor->details,
                    'source' => $factor->source,
                ],
                $assessment->riskFactors
            ),
            'assessedAt' => $assessment->assessedAt->format('Y-m-d H:i:s'),
            'assessedBy' => $assessment->assessedBy,
            'expiresAt' => $assessment->nextReviewDate?->format('Y-m-d'),
            'recommendations' => [],
        ];
    }

    /**
     * Build documents data array from profile.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildDocumentsData(KycProfile $profile): array
    {
        $documents = [];
        
        foreach ($profile->documents as $doc) {
            $documents[] = [
                'documentId' => $doc->documentId,
                'documentType' => $doc->documentType->value,
                'status' => $doc->status->value,
                'verifiedAt' => $doc->verifiedAt->format('Y-m-d H:i:s'),
                'verifiedBy' => $doc->verifierId,
                'expiryDate' => $doc->expiryDate?->format('Y-m-d'),
                'verificationMethod' => $doc->verificationMethod,
                'confidenceScore' => $doc->confidenceScore,
            ];
        }

        return $documents;
    }

    /**
     * Build review schedule data array.
     *
     * @return array<string, mixed>|null
     */
    private function buildReviewScheduleData(KycProfile $profile): ?array
    {
        if ($profile->nextReviewDate === null) {
            return null;
        }

        return [
            'nextReviewDate' => $profile->nextReviewDate->format('Y-m-d'),
            'frequency' => 'annual',
            'triggerConditions' => [],
            'lastReviewDate' => null,
            'overdue' => $profile->isReviewDue(),
        ];
    }

    /**
     * Build verification history from profile.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildVerificationHistory(KycProfile $profile): array
    {
        return [
            [
                'status' => $profile->status->value,
                'changedAt' => $profile->updatedAt?->format('Y-m-d H:i:s') ?? '',
                'changedBy' => null,
                'reason' => null,
            ],
        ];
    }
}
