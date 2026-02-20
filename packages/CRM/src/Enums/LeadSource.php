<?php

declare(strict_types=1);

namespace Nexus\CRM\Enums;

/**
 * Lead Source Enum
 * 
 * Represents the source/channel through which a lead was acquired.
 * 
 * @package Nexus\CRM\Enums
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
enum LeadSource: string
{
    case Website = 'website';
    case Referral = 'referral';
    case ColdOutreach = 'cold_outreach';
    case TradeShow = 'trade_show';
    case SocialMedia = 'social_media';
    case EmailCampaign = 'email_campaign';
    case PaidAds = 'paid_ads';
    case OrganicSearch = 'organic_search';
    case DirectInquiry = 'direct_inquiry';
    case Partner = 'partner';
    case Other = 'other';

    /**
     * Get human-readable label for the source
     */
    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Referral => 'Referral',
            self::ColdOutreach => 'Cold Outreach',
            self::TradeShow => 'Trade Show',
            self::SocialMedia => 'Social Media',
            self::EmailCampaign => 'Email Campaign',
            self::PaidAds => 'Paid Advertising',
            self::OrganicSearch => 'Organic Search',
            self::DirectInquiry => 'Direct Inquiry',
            self::Partner => 'Partner',
            self::Other => 'Other',
        };
    }

    /**
     * Check if this is an inbound source
     */
    public function isInbound(): bool
    {
        return in_array($this, [
            self::Website,
            self::Referral,
            self::SocialMedia,
            self::OrganicSearch,
            self::DirectInquiry,
            self::Partner,
        ], true);
    }

    /**
     * Check if this is an outbound source
     */
    public function isOutbound(): bool
    {
        return in_array($this, [
            self::ColdOutreach,
            self::EmailCampaign,
            self::PaidAds,
        ], true);
    }

    /**
     * Check if this is a paid source
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::PaidAds,
            self::TradeShow,
        ], true);
    }

    /**
     * Get category for the source
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::Website, self::OrganicSearch, self::DirectInquiry => 'Organic',
            self::Referral, self::Partner => 'Relationship',
            self::ColdOutreach, self::EmailCampaign => 'Outbound',
            self::PaidAds, self::TradeShow => 'Paid',
            self::SocialMedia => 'Social',
            self::Other => 'Uncategorized',
        };
    }
}