<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a statement output format.
 */
enum StatementFormat: string
{
    case JSON = 'json';
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case HTML = 'html';
    case XBRL = 'xbrl';

    /**
     * Get the MIME type for this format.
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::JSON => 'application/json',
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
            self::HTML => 'text/html',
            self::XBRL => 'application/xml',
        };
    }

    /**
     * Get the file extension for this format.
     */
    public function getFileExtension(): string
    {
        return match ($this) {
            self::JSON => 'json',
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
            self::CSV => 'csv',
            self::HTML => 'html',
            self::XBRL => 'xbrl',
        };
    }
}
