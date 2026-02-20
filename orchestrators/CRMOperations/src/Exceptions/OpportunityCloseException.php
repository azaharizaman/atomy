<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

/**
 * Opportunity Close Exception
 * 
 * Thrown when opportunity closing operations fail.
 * 
 * @package Nexus\CRMOperations\Exceptions
 */
final class OpportunityCloseException extends CRMOperationsException
{
    /**
     * Create for invalid stage transition
     */
    public static function invalidStageTransition(
        string $opportunityId,
        string $currentStage,
        string $targetStage
    ): self {
        return new self(
            message: "Cannot transition opportunity {$opportunityId} from '{$currentStage}' to '{$targetStage}'",
            context: "opportunity_id: {$opportunityId}, from: {$currentStage}, to: {$targetStage}"
        );
    }

    /**
     * Create for missing quotation
     */
    public static function missingQuotation(string $opportunityId): self
    {
        return new self(
            message: "Cannot close opportunity {$opportunityId} without a quotation",
            context: "opportunity_id: {$opportunityId}"
        );
    }

    /**
     * Create for approval required
     */
    public static function approvalRequired(
        string $opportunityId,
        string $approvalLevel
    ): self {
        return new self(
            message: "Opportunity {$opportunityId} requires {$approvalLevel} approval",
            context: "opportunity_id: {$opportunityId}, level: {$approvalLevel}"
        );
    }

    /**
     * Create for close failure
     */
    public static function closeFailed(string $opportunityId, string $reason): self
    {
        return new self(
            message: "Failed to close opportunity {$opportunityId}: {$reason}",
            context: "opportunity_id: {$opportunityId}"
        );
    }
}
