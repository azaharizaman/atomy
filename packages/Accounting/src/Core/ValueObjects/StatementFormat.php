<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Statement export format.
 *
 * Represents available output formats for financial statements.
 */
enum StatementFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case HTML = 'html';
    case XML = 'xml';

    /**
     * Get the MIME type for this format.
     */
    public function getMimeType(): string
    {
        return match($this) {
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::HTML => 'text/html',
            self::XML => 'application/xml',
        };
    }

    /**
     * Get the file extension for this format.
     */
    public function getExtension(): string
    {
        return match($this) {
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
            self::CSV => 'csv',
            self::JSON => 'json',
            self::HTML => 'html',
            self::XML => 'xml',
        };
    }

    /**
     * Check if this format supports formatting options.
     */
    public function supportsFormatting(): bool
    {
        return match($this) {
            self::PDF, self::EXCEL, self::HTML => true,
            self::CSV, self::JSON, self::XML => false,
        };
    }

    /**
     * Check if this format is binary.
     */
    public function isBinary(): bool
    {
        return match($this) {
            self::PDF, self::EXCEL => true,
            self::CSV, self::JSON, self::HTML, self::XML => false,
        };
    }
}
