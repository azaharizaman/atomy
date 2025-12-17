<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Standard purposes for which consent may be obtained.
 *
 * These are common purposes; applications can define additional
 * custom purposes as needed.
 */
enum ConsentPurpose: string
{
    /**
     * Core service delivery - usually not requiring separate consent.
     */
    case SERVICE_DELIVERY = 'service_delivery';

    /**
     * Marketing communications via email.
     */
    case MARKETING_EMAIL = 'marketing_email';

    /**
     * Marketing communications via SMS.
     */
    case MARKETING_SMS = 'marketing_sms';

    /**
     * Marketing communications via phone.
     */
    case MARKETING_PHONE = 'marketing_phone';

    /**
     * Third-party marketing (sharing with partners).
     */
    case THIRD_PARTY_MARKETING = 'third_party_marketing';

    /**
     * Analytics and service improvement.
     */
    case ANALYTICS = 'analytics';

    /**
     * Personalization of service/content.
     */
    case PERSONALIZATION = 'personalization';

    /**
     * Profiling for automated decisions.
     */
    case PROFILING = 'profiling';

    /**
     * Location tracking.
     */
    case LOCATION_TRACKING = 'location_tracking';

    /**
     * Cookie/tracking technology usage.
     */
    case COOKIES = 'cookies';

    /**
     * Research and statistical purposes.
     */
    case RESEARCH = 'research';

    /**
     * Processing of sensitive/special category data.
     */
    case SENSITIVE_DATA = 'sensitive_data';

    /**
     * Cross-border data transfer.
     */
    case CROSS_BORDER_TRANSFER = 'cross_border_transfer';

    /**
     * Newsletter subscription.
     */
    case NEWSLETTER = 'newsletter';

    /**
     * Processing children's data (requires parental consent).
     */
    case CHILDREN_DATA = 'children_data';

    /**
     * Check if this purpose typically requires explicit consent.
     */
    public function requiresExplicitConsent(): bool
    {
        return match ($this) {
            self::MARKETING_EMAIL,
            self::MARKETING_SMS,
            self::MARKETING_PHONE,
            self::THIRD_PARTY_MARKETING,
            self::PROFILING,
            self::LOCATION_TRACKING,
            self::SENSITIVE_DATA,
            self::CROSS_BORDER_TRANSFER,
            self::CHILDREN_DATA => true,
            default => false,
        };
    }

    /**
     * Check if this purpose requires opt-in consent.
     */
    public function requiresOptIn(): bool
    {
        return $this->requiresExplicitConsent();
    }

    /**
     * Check if this purpose requires parental consent.
     */
    public function requiresParentalConsent(): bool
    {
        return $this === self::CHILDREN_DATA;
    }

    /**
     * Check if consent for this purpose can be bundled with others.
     */
    public function canBeBundled(): bool
    {
        return match ($this) {
            self::THIRD_PARTY_MARKETING,
            self::PROFILING,
            self::SENSITIVE_DATA,
            self::CHILDREN_DATA => false, // Must be separate consent
            default => true,
        };
    }

    /**
     * Check if this purpose is related to marketing.
     */
    public function isMarketingRelated(): bool
    {
        return match ($this) {
            self::MARKETING_EMAIL,
            self::MARKETING_SMS,
            self::MARKETING_PHONE,
            self::THIRD_PARTY_MARKETING,
            self::NEWSLETTER => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SERVICE_DELIVERY => 'Service Delivery',
            self::MARKETING_EMAIL => 'Email Marketing',
            self::MARKETING_SMS => 'SMS Marketing',
            self::MARKETING_PHONE => 'Phone Marketing',
            self::THIRD_PARTY_MARKETING => 'Third-Party Marketing',
            self::ANALYTICS => 'Analytics',
            self::PERSONALIZATION => 'Personalization',
            self::PROFILING => 'Automated Profiling',
            self::LOCATION_TRACKING => 'Location Tracking',
            self::COOKIES => 'Cookies & Tracking',
            self::RESEARCH => 'Research & Statistics',
            self::SENSITIVE_DATA => 'Sensitive Data Processing',
            self::CROSS_BORDER_TRANSFER => 'Cross-Border Transfer',
            self::NEWSLETTER => 'Newsletter',
            self::CHILDREN_DATA => 'Children\'s Data',
        };
    }

    /**
     * Get description for consent forms.
     */
    public function getConsentDescription(): string
    {
        return match ($this) {
            self::SERVICE_DELIVERY => 'Processing necessary to provide you with our core services.',
            self::MARKETING_EMAIL => 'We would like to send you marketing communications via email.',
            self::MARKETING_SMS => 'We would like to send you marketing communications via SMS.',
            self::MARKETING_PHONE => 'We would like to contact you for marketing purposes via phone.',
            self::THIRD_PARTY_MARKETING => 'We would like to share your data with our partners for their marketing purposes.',
            self::ANALYTICS => 'We use analytics to understand how you use our services and improve them.',
            self::PERSONALIZATION => 'We personalize your experience based on your preferences and usage.',
            self::PROFILING => 'We may make automated decisions about you based on your profile.',
            self::LOCATION_TRACKING => 'We collect and process your location data.',
            self::COOKIES => 'We use cookies and similar technologies to enhance your experience.',
            self::RESEARCH => 'We may use your data for research and statistical analysis.',
            self::SENSITIVE_DATA => 'We process special categories of personal data (health, biometric, etc.).',
            self::CROSS_BORDER_TRANSFER => 'We may transfer your data to countries outside your jurisdiction.',
            self::NEWSLETTER => 'We would like to send you our newsletter.',
            self::CHILDREN_DATA => 'We process personal data of children under the age of consent.',
        };
    }
}
