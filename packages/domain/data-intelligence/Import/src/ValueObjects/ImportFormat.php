<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Import format enumeration with capability detection
 * 
 * Defines all supported input formats and their characteristics.
 * Native parsers (CSV, JSON, XML) are in package; Excel parser is in Atomy.
 */
enum ImportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case EXCEL = 'excel';

    /**
     * Get MIME type for file validation
     */
    public function getMimeType(): string
    {
        return match($this) {
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }

    /**
     * Get file extension
     */
    public function getFileExtension(): string
    {
        return match($this) {
            self::CSV => 'csv',
            self::JSON => 'json',
            self::XML => 'xml',
            self::EXCEL => 'xlsx',
        };
    }

    /**
     * Check if format requires external parser (implemented in Atomy)
     */
    public function requiresExternalParser(): bool
    {
        return match($this) {
            self::EXCEL => true,
            self::CSV, self::JSON, self::XML => false,
        };
    }

    /**
     * Check if format supports streaming for large datasets
     */
    public function supportsStreaming(): bool
    {
        return match($this) {
            self::CSV => true,
            self::JSON, self::XML, self::EXCEL => false,
        };
    }

    /**
     * Get human-readable format name
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CSV => 'CSV File',
            self::JSON => 'JSON Data',
            self::XML => 'XML Document',
            self::EXCEL => 'Excel Spreadsheet',
        };
    }
}
