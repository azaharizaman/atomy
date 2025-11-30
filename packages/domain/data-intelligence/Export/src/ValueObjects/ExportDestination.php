<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Export destination enumeration with routing logic
 * 
 * Defines where the exported output should be delivered.
 * Determines post-processing requirements (rate limiting, authentication, etc.)
 */
enum ExportDestination: string
{
    case DOWNLOAD = 'download';
    case EMAIL = 'email';
    case STORAGE = 'storage';
    case PRINTER = 'printer';
    case WEBHOOK = 'webhook';
    case DOCUMENT_LIBRARY = 'document_library';

    /**
     * Check if destination requires rate limiting
     * 
     * Prevents overwhelming external services with export requests
     */
    public function requiresRateLimit(): bool
    {
        return match($this) {
            self::WEBHOOK, self::EMAIL => true,
            self::DOWNLOAD, self::STORAGE, self::PRINTER, self::DOCUMENT_LIBRARY => false,
        };
    }

    /**
     * Check if destination requires authentication
     */
    public function requiresAuth(): bool
    {
        return match($this) {
            self::WEBHOOK, self::EMAIL, self::DOCUMENT_LIBRARY => true,
            self::DOWNLOAD, self::STORAGE, self::PRINTER => false,
        };
    }

    /**
     * Check if destination is synchronous (blocks until complete)
     */
    public function isSynchronous(): bool
    {
        return match($this) {
            self::DOWNLOAD => true,
            self::EMAIL, self::STORAGE, self::PRINTER, self::WEBHOOK, self::DOCUMENT_LIBRARY => false,
        };
    }

    /**
     * Get human-readable destination name
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DOWNLOAD => 'Direct Download',
            self::EMAIL => 'Email Delivery',
            self::STORAGE => 'Cloud Storage',
            self::PRINTER => 'Print Queue',
            self::WEBHOOK => 'Webhook Notification',
            self::DOCUMENT_LIBRARY => 'Document Library',
        };
    }
}
