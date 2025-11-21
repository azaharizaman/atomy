<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Supported report output formats.
 */
enum ReportFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case HTML = 'html';

    /**
     * Get a human-readable label for the format.
     */
    public function label(): string
    {
        return match ($this) {
            self::PDF => 'PDF Document',
            self::EXCEL => 'Excel Spreadsheet',
            self::CSV => 'CSV File',
            self::JSON => 'JSON Data',
            self::HTML => 'HTML Document',
        };
    }

    /**
     * Get the file extension for this format.
     */
    public function extension(): string
    {
        return match ($this) {
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
            self::CSV => 'csv',
            self::JSON => 'json',
            self::HTML => 'html',
        };
    }

    /**
     * Get the MIME type for this format.
     */
    public function mimeType(): string
    {
        return match ($this) {
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::HTML => 'text/html',
        };
    }

    /**
     * Check if this format supports streaming for large datasets.
     */
    public function supportsStreaming(): bool
    {
        return match ($this) {
            self::CSV, self::JSON => true,
            self::PDF, self::EXCEL, self::HTML => false,
        };
    }
}
