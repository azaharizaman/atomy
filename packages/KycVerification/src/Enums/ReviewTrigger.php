<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Triggers for KYC review.
 * 
 * Defines events that can trigger a KYC review cycle.
 */
enum ReviewTrigger: string
{
    /**
     * Scheduled periodic review
     */
    case SCHEDULED = 'scheduled';

    /**
     * Risk level changed
     */
    case RISK_CHANGE = 'risk_change';

    /**
     * Document expired
     */
    case DOCUMENT_EXPIRED = 'document_expired';

    /**
     * Suspicious activity detected
     */
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    /**
     * Regulatory requirement
     */
    case REGULATORY = 'regulatory';

    /**
     * Manual review requested
     */
    case MANUAL_REQUEST = 'manual_request';

    /**
     * Party information changed
     */
    case INFORMATION_CHANGE = 'information_change';

    /**
     * Transaction threshold exceeded
     */
    case TRANSACTION_THRESHOLD = 'transaction_threshold';

    /**
     * Adverse media detected
     */
    case ADVERSE_MEDIA = 'adverse_media';

    /**
     * Sanctions list update
     */
    case SANCTIONS_UPDATE = 'sanctions_update';

    /**
     * Beneficial ownership change
     */
    case UBO_CHANGE = 'ubo_change';

    /**
     * New jurisdiction added
     */
    case NEW_JURISDICTION = 'new_jurisdiction';

    /**
     * Initial onboarding
     */
    case ONBOARDING = 'onboarding';

    /**
     * Check if this trigger requires immediate action
     */
    public function isUrgent(): bool
    {
        return in_array($this, [
            self::SUSPICIOUS_ACTIVITY,
            self::SANCTIONS_UPDATE,
            self::ADVERSE_MEDIA,
        ], true);
    }

    /**
     * Get priority level (1 = highest, 5 = lowest)
     */
    public function priority(): int
    {
        return match ($this) {
            self::SUSPICIOUS_ACTIVITY => 1,
            self::SANCTIONS_UPDATE => 1,
            self::ADVERSE_MEDIA => 2,
            self::REGULATORY => 2,
            self::DOCUMENT_EXPIRED => 3,
            self::RISK_CHANGE => 3,
            self::UBO_CHANGE => 3,
            self::NEW_JURISDICTION => 3,
            self::TRANSACTION_THRESHOLD => 3,
            self::INFORMATION_CHANGE => 4,
            self::MANUAL_REQUEST => 4,
            self::SCHEDULED => 5,
            self::ONBOARDING => 5,
        };
    }

    /**
     * Get SLA in hours for this trigger type
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::SUSPICIOUS_ACTIVITY => 4,
            self::SANCTIONS_UPDATE => 24,
            self::ADVERSE_MEDIA => 48,
            self::REGULATORY => 72,
            self::DOCUMENT_EXPIRED => 168,  // 7 days
            self::RISK_CHANGE => 72,
            self::UBO_CHANGE => 72,
            self::NEW_JURISDICTION => 72,
            self::TRANSACTION_THRESHOLD => 48,
            self::INFORMATION_CHANGE => 168, // 7 days
            self::MANUAL_REQUEST => 168,     // 7 days
            self::SCHEDULED => 336,          // 14 days
            self::ONBOARDING => 72,
        };
    }

    /**
     * Check if review can be automated
     */
    public function canAutomate(): bool
    {
        return in_array($this, [
            self::SCHEDULED,
            self::DOCUMENT_EXPIRED,
            self::TRANSACTION_THRESHOLD,
        ], true);
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled Periodic Review',
            self::RISK_CHANGE => 'Risk Level Change',
            self::DOCUMENT_EXPIRED => 'Document Expired',
            self::SUSPICIOUS_ACTIVITY => 'Suspicious Activity',
            self::REGULATORY => 'Regulatory Requirement',
            self::MANUAL_REQUEST => 'Manual Review Request',
            self::INFORMATION_CHANGE => 'Information Change',
            self::TRANSACTION_THRESHOLD => 'Transaction Threshold',
            self::ADVERSE_MEDIA => 'Adverse Media',
            self::SANCTIONS_UPDATE => 'Sanctions List Update',
            self::UBO_CHANGE => 'Beneficial Ownership Change',
            self::NEW_JURISDICTION => 'New Jurisdiction',
            self::ONBOARDING => 'Initial Onboarding',
        };
    }
}
