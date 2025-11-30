<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Document type enumeration.
 *
 * Defines the classification categories for documents in the system.
 */
enum DocumentType: string
{
    case CONTRACT = 'contract';
    case INVOICE = 'invoice';
    case REPORT = 'report';
    case IMAGE = 'image';
    case SPREADSHEET = 'spreadsheet';
    case PRESENTATION = 'presentation';
    case PDF = 'pdf';
    case OTHER = 'other';

    /**
     * Get a human-readable label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTRACT => 'Contract',
            self::INVOICE => 'Invoice',
            self::REPORT => 'Report',
            self::IMAGE => 'Image',
            self::SPREADSHEET => 'Spreadsheet',
            self::PRESENTATION => 'Presentation',
            self::PDF => 'PDF Document',
            self::OTHER => 'Other',
        };
    }
}
