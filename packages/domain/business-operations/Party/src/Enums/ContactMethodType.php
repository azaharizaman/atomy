<?php

declare(strict_types=1);

namespace Nexus\Party\Enums;

/**
 * Contact method type enumeration.
 * 
 * Defines the type of contact information.
 */
enum ContactMethodType: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case MOBILE = 'mobile';
    case FAX = 'fax';
    case WHATSAPP = 'whatsapp';
    case WECHAT = 'wechat';
    case TELEGRAM = 'telegram';
    case WEBSITE = 'website';
    
    /**
     * Check if this contact method requires validation (email/phone format).
     */
    public function requiresValidation(): bool
    {
        return match($this) {
            self::EMAIL, self::PHONE, self::MOBILE, self::FAX => true,
            default => false,
        };
    }
    
    /**
     * Check if this contact method is a messaging app.
     */
    public function isMessagingApp(): bool
    {
        return match($this) {
            self::WHATSAPP, self::WECHAT, self::TELEGRAM => true,
            default => false,
        };
    }
    
    /**
     * Get validation regex pattern for this contact method type.
     */
    public function getValidationPattern(): ?string
    {
        return match($this) {
            self::EMAIL => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            self::PHONE, self::MOBILE, self::FAX => '/^\+?[0-9\s\-\(\)]{7,20}$/',
            default => null,
        };
    }
    
    /**
     * Get human-readable label for this contact method type.
     */
    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::PHONE => 'Phone',
            self::MOBILE => 'Mobile',
            self::FAX => 'Fax',
            self::WHATSAPP => 'WhatsApp',
            self::WECHAT => 'WeChat',
            self::TELEGRAM => 'Telegram',
            self::WEBSITE => 'Website',
        };
    }
}
