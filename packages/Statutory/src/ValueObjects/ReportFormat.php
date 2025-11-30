<?php

declare(strict_types=1);

namespace Nexus\Statutory\ValueObjects;

/**
 * Immutable value object representing statutory report output formats.
 */
enum ReportFormat: string
{
    case JSON = 'json';
    case XML = 'xml';
    case XBRL = 'xbrl';
    case CSV = 'csv';
    case PDF = 'pdf';
    case EXCEL = 'excel';

    /**
     * Get the MIME type for this format.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::XBRL => 'application/xbrl+xml',
            self::CSV => 'text/csv',
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }

    /**
     * Get the file extension for this format.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return match ($this) {
            self::JSON => 'json',
            self::XML => 'xml',
            self::XBRL => 'xbrl',
            self::CSV => 'csv',
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
        };
    }

    /**
     * Check if this format is machine-readable.
     *
     * @return bool
     */
    public function isMachineReadable(): bool
    {
        return match ($this) {
            self::JSON, self::XML, self::XBRL, self::CSV => true,
            self::PDF, self::EXCEL => false,
        };
    }

    /**
     * Check if this format supports digital signatures.
     *
     * @return bool
     */
    public function supportsDigitalSignature(): bool
    {
        return match ($this) {
            self::XML, self::XBRL, self::PDF => true,
            self::JSON, self::CSV, self::EXCEL => false,
        };
    }
}
