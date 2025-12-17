<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Services;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\KycVerification\Enums\ReviewTrigger;
use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\Enums\VerificationStatus;
use Nexus\KycVerification\Contracts\RiskAssessorInterface;
use Nexus\KycVerification\ValueObjects\VerificationResult;
use Nexus\KycVerification\ValueObjects\DocumentVerification;
use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\ReviewSchedulerInterface;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\Contracts\KycProfilePersistInterface;
use Nexus\KycVerification\Exceptions\VerificationFailedException;
use Nexus\KycVerification\Contracts\KycVerificationManagerInterface;
use Nexus\KycVerification\Contracts\Providers\PartyProviderInterface;
use Nexus\KycVerification\Exceptions\InvalidStatusTransitionException;
use Nexus\KycVerification\Contracts\BeneficialOwnershipTrackerInterface;
use Nexus\KycVerification\Contracts\Providers\AuditLoggerProviderInterface;

/**
 * KYC Verification Manager - orchestrates KYC verification lifecycle.
 */
final readonly class KycVerificationManager implements KycVerificationManagerInterface
{
    public function __construct(
        private KycProfileQueryInterface $profileQuery,
        private KycProfilePersistInterface $profilePersist,
        private RiskAssessorInterface $riskAssessor,
        private BeneficialOwnershipTrackerInterface $ownershipTracker,
        private ReviewSchedulerInterface $reviewScheduler,
        private PartyProviderInterface $partyProvider,
        private AuditLoggerProviderInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function initiateVerification(
        string $partyId,
        DueDiligenceLevel $dueDiligenceLevel = DueDiligenceLevel::STANDARD,
        array $partyData = []
    ): VerificationResult {
        try {
            // Check if profile already exists
            $existingProfile = $this->profileQuery->findByPartyId($partyId);
            if ($existingProfile !== null) {
                $this->logger->info('KYC profile already exists for party', [
                    'party_id' => $partyId,
                    'status' => $existingProfile->status->value,
                ]);
                
                return VerificationResult::pending(
                    partyId: $partyId,
                    message: 'KYC profile already exists',
                    details: ['current_status' => $existingProfile->status->value]
                );
            }

            // Verify party exists
            $partyType = $this->partyProvider->getPartyType($partyId);
            if ($partyType === null) {
                throw KycVerificationException::forParty(
                    $partyId,
                    'Party not found'
                );
            }

            // Determine required documents based on due diligence level
            $requiredDocuments = $dueDiligenceLevel->requiredDocumentTypes();
            
            // Create initial risk assessment
            $riskAssessment = $this->riskAssessor->assess($partyId, $partyData);

            // Create initial profile
            $profile = new KycProfile(
                partyId: $partyId,
                partyType: $partyType,
                status: VerificationStatus::PENDING,
                dueDiligenceLevel: $dueDiligenceLevel,
                riskAssessment: $riskAssessment,
                documents: [],
                beneficialOwners: [],
                verificationScore: 0,
                createdAt: new \DateTimeImmutable(),
                additionalData: array_merge($partyData, [
                    'required_documents' => array_map(
                        fn($doc) => $doc->value,
                        $requiredDocuments
                    ),
                ])
            );

            // Persist profile
            $savedProfile = $this->profilePersist->save($profile);

            // Schedule initial review
            $this->reviewScheduler->scheduleReview(
                $partyId,
                ReviewTrigger::ONBOARDING
            );

            // Log audit event
            $this->auditLogger->logVerificationEvent(
                partyId: $partyId,
                eventType: 'verification_initiated',
                details: [
                    'due_diligence_level' => $dueDiligenceLevel->value,
                    'risk_level' => $riskAssessment->riskLevel->value,
                    'required_documents' => count($requiredDocuments),
                ],
                performedBy: $partyData['initiated_by'] ?? null
            );

            $this->logger->info('KYC verification initiated', [
                'party_id' => $partyId,
                'due_diligence_level' => $dueDiligenceLevel->value,
            ]);

            return VerificationResult::pending(
                partyId: $partyId,
                message: 'KYC verification initiated',
                details: [
                    'status' => VerificationStatus::PENDING->value,
                    'due_diligence_level' => $dueDiligenceLevel->value,
                    'risk_level' => $riskAssessment->riskLevel->value,
                    'required_documents' => count($requiredDocuments),
                ]
            );
        } catch (KycVerificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initiate verification', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);
            throw KycVerificationException::forParty(
                $partyId,
                'Failed to initiate verification: ' . $e->getMessage(),
                $e
            );
        }
    }

    public function getProfile(string $partyId): ?KycProfile
    {
        return $this->profileQuery->findByPartyId($partyId);
    }

    public function updateStatus(
        string $partyId,
        VerificationStatus $newStatus,
        ?string $reason = null,
        ?string $updatedBy = null
    ): VerificationResult {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        $currentStatus = $profile->status;
        
        // Validate transition
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw InvalidStatusTransitionException::create(
                $currentStatus,
                $newStatus,
                $currentStatus->allowedTransitions()
            );
        }

        // Update profile with new status
        $updatedProfile = $profile->withStatus($newStatus);
        
        // If verified, update last verified timestamp
        if ($newStatus === VerificationStatus::VERIFIED) {
            $updatedProfile = $updatedProfile->withLastVerifiedAt(new \DateTimeImmutable());
            
            // Calculate next review date
            $nextReviewDate = $this->reviewScheduler->calculateNextReviewDate($partyId);
            $updatedProfile = $updatedProfile->withNextReviewDate($nextReviewDate);
        }

        $this->profilePersist->save($updatedProfile);

        // Log audit event
        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            eventType: 'status_changed',
            details: [
                'previous_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
            ],
            performedBy: $updatedBy
        );

        $this->logger->info('KYC status updated', [
            'party_id' => $partyId,
            'from' => $currentStatus->value,
            'to' => $newStatus->value,
        ]);

        return VerificationResult::success(
            partyId: $partyId,
            message: 'Status updated successfully',
            details: [
                'previous_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
            ]
        );
    }

    public function addDocumentVerification(
        string $partyId,
        DocumentVerification $documentVerification
    ): VerificationResult {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Add document to profile
        $verifiedDocuments = $profile->verifiedDocuments;
        $verifiedDocuments[] = $documentVerification;

        $updatedProfile = $profile->withVerifiedDocuments($verifiedDocuments);
        
        // Recalculate verification score
        $newScore = $this->calculateVerificationScore($partyId);
        $updatedProfile = $updatedProfile->withVerificationScore($newScore);

        // Persist document
        $this->profilePersist->saveDocumentVerification($partyId, $documentVerification);
        $this->profilePersist->save($updatedProfile);

        // Log audit event
        $this->auditLogger->logDocumentVerification(
            partyId: $partyId,
            documentType: $documentVerification->documentType->value,
            isVerified: $documentVerification->isVerified,
            confidence: $documentVerification->confidence,
            verifiedBy: null
        );

        // Check if document was rejected
        if (!$documentVerification->isVerified) {
            return VerificationResult::conditional(
                partyId: $partyId,
                message: 'Document verification failed',
                conditions: [$documentVerification->rejectionReason ?? 'Document could not be verified'],
                details: [
                    'document_type' => $documentVerification->documentType->value,
                    'confidence' => $documentVerification->confidence,
                ]
            );
        }

        return VerificationResult::success(
            partyId: $partyId,
            message: 'Document added successfully',
            details: [
                'document_type' => $documentVerification->documentType->value,
                'verification_score' => $newScore,
            ]
        );
    }

    public function completeVerification(
        string $partyId,
        ?string $verifiedBy = null,
        array $additionalData = []
    ): VerificationResult {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Check completeness
        $missingRequirements = $profile->getMissingRequirements();
        if (!empty($missingRequirements)) {
            return VerificationResult::conditional(
                partyId: $partyId,
                message: 'Verification incomplete - missing requirements',
                conditions: $missingRequirements,
                details: [
                    'completeness_score' => $profile->getCompletenessScore(),
                ]
            );
        }

        // Check risk level
        if ($this->riskAssessor->isBlocked($partyId)) {
            return VerificationResult::failed(
                partyId: $partyId,
                message: 'Party is blocked due to prohibited risk level',
                reasons: ['Risk level is PROHIBITED']
            );
        }

        // Check ownership requirements for corporate entities
        if ($this->ownershipTracker->isUboTrackingRequired($partyId)) {
            if (!$this->ownershipTracker->isOwnershipComplete($partyId)) {
                $totalIdentified = $this->ownershipTracker->getTotalOwnershipIdentified($partyId);
                return VerificationResult::conditional(
                    partyId: $partyId,
                    message: 'Beneficial ownership incomplete',
                    conditions: [
                        sprintf('Only %.2f%% of ownership identified', $totalIdentified),
                    ],
                    details: [
                        'ownership_identified' => $totalIdentified,
                    ]
                );
            }
        }

        // Update status to verified
        return $this->updateStatus(
            $partyId,
            VerificationStatus::VERIFIED,
            'Verification completed successfully',
            $verifiedBy
        );
    }

    public function rejectVerification(
        string $partyId,
        array $reasons,
        ?string $rejectedBy = null
    ): VerificationResult {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Update status to rejected
        $result = $this->updateStatus(
            $partyId,
            VerificationStatus::REJECTED,
            implode('; ', $reasons),
            $rejectedBy
        );

        // Log rejection with reasons
        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            eventType: 'verification_rejected',
            details: [
                'reasons' => $reasons,
            ],
            performedBy: $rejectedBy
        );

        return VerificationResult::failed(
            partyId: $partyId,
            message: 'Verification rejected',
            reasons: $reasons
        );
    }

    public function isVerified(string $partyId): bool
    {
        $status = $this->getStatus($partyId);
        return $status === VerificationStatus::VERIFIED;
    }

    public function canTransact(string $partyId): bool
    {
        $status = $this->getStatus($partyId);
        return $status?->allowsTransactions() ?? false;
    }

    public function getStatus(string $partyId): ?VerificationStatus
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        return $profile?->status;
    }

    public function triggerReverification(
        string $partyId,
        string $reason,
        ?string $triggeredBy = null
    ): VerificationResult {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Update status to pending re-verification
        $result = $this->updateStatus(
            $partyId,
            VerificationStatus::PENDING_REVERIFICATION,
            $reason,
            $triggeredBy
        );

        // Schedule review
        $this->reviewScheduler->scheduleReview(
            $partyId,
            ReviewTrigger::SCHEDULED
        );

        return $result;
    }

    public function calculateVerificationScore(string $partyId): int
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            return 0;
        }

        $score = 0;
        
        // Document verification contributes up to 60 points
        foreach ($profile->verifiedDocuments as $document) {
            if ($document->isVerified) {
                $score += $document->documentType->verificationWeight();
            }
        }

        // Cap document score at 60
        $documentScore = min(60, $score);

        // Risk assessment contributes up to 20 points
        $riskScore = 0;
        if ($profile->riskAssessment !== null) {
            $riskScore = match ($profile->riskAssessment->riskLevel->value) {
                'low' => 20,
                'medium' => 15,
                'high' => 10,
                'very_high' => 5,
                'prohibited' => 0,
            };
        }

        // Beneficial ownership contributes up to 20 points for corporate entities
        $ownershipScore = 0;
        if ($this->ownershipTracker->isUboTrackingRequired($partyId)) {
            $totalIdentified = $this->ownershipTracker->getTotalOwnershipIdentified($partyId);
            $ownershipScore = (int) ($totalIdentified * 0.2); // 20 points for 100%
        } else {
            // Non-corporate entities get full ownership points
            $ownershipScore = 20;
        }

        return $documentScore + $riskScore + $ownershipScore;
    }

    public function getPendingVerifications(?int $limit = null): array
    {
        $profiles = $this->profileQuery->findPending($limit);
        return array_map(fn(KycProfile $p) => $p->partyId, $profiles);
    }

    public function getExpiringVerifications(int $withinDays = 30): array
    {
        $profiles = $this->profileQuery->findExpiring($withinDays);
        return array_map(fn(KycProfile $p) => $p->partyId, $profiles);
    }
}
