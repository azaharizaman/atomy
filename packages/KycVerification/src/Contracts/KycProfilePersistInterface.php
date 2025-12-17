<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\ValueObjects\BeneficialOwner;
use Nexus\KycVerification\ValueObjects\DocumentVerification;
use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\ValueObjects\RiskAssessment;
use Nexus\KycVerification\ValueObjects\ReviewSchedule;

/**
 * Interface for KYC profile persistence (Persist operations).
 */
interface KycProfilePersistInterface
{
    /**
     * Save KYC profile
     */
    public function save(KycProfile $profile): KycProfile;

    /**
     * Delete KYC profile
     */
    public function delete(string $partyId): void;

    /**
     * Save risk assessment for a party
     */
    public function saveRiskAssessment(string $partyId, RiskAssessment $assessment): void;

    /**
     * Save document verification
     */
    public function saveDocumentVerification(
        string $partyId,
        DocumentVerification $document
    ): void;

    /**
     * Delete document verification
     */
    public function deleteDocumentVerification(
        string $partyId,
        string $documentId
    ): void;

    /**
     * Save beneficial owner
     */
    public function saveBeneficialOwner(
        string $partyId,
        BeneficialOwner $beneficialOwner
    ): void;

    /**
     * Delete beneficial owner
     */
    public function deleteBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId
    ): void;

    /**
     * Save review schedule
     */
    public function saveReviewSchedule(ReviewSchedule $schedule): void;

    /**
     * Delete review schedule
     */
    public function deleteReviewSchedule(
        string $partyId,
        string $scheduleId
    ): void;
}
