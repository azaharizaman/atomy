<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Export format enumeration with capability detection
 * 
 * Defines all supported output formats and their characteristics.
 * Formatters implement format-specific logic in application layer (Atomy).
 */
enum ExportFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case HTML = 'html';
    case TXT = 'txt';
    case PRINTER = 'printer';

    /**
     * Get MIME type for HTTP response headers
     */
    public function getMimeType(): string
    {
        return match($this) {
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::HTML => 'text/html',
            self::TXT => 'text/plain',
            self::PRINTER => 'application/octet-stream',
        };
    }

    /**
     * Get file extension for saved files
     */
    public function getFileExtension(): string
    {
        return match($this) {
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
            self::CSV => 'csv',
            self::JSON => 'json',
            self::XML => 'xml',
            self::HTML => 'html',
            self::TXT => 'txt',
            self::PRINTER => 'ps', // PostScript
        };
    }

    /**
     * Check if format produces binary output
     */
    public function isBinary(): bool
    {
        return match($this) {
            self::PDF, self::EXCEL, self::PRINTER => true,
            self::CSV, self::JSON, self::XML, self::HTML, self::TXT => false,
        };
    }

    /**
     * Check if format supports streaming for large datasets
     */
    public function supportsStreaming(): bool
    {
        return match($this) {
            self::CSV, self::TXT, self::JSON => true,
            self::PDF, self::EXCEL, self::XML, self::HTML, self::PRINTER => false,
        };
    }

    /**
     * Check if format requires template rendering
     */
    public function requiresTemplate(): bool
    {
        return match($this) {
            self::PDF, self::HTML, self::PRINTER => true,
            self::EXCEL, self::CSV, self::JSON, self::XML, self::TXT => false,
        };
    }

    /**
     * Get human-readable format name
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PDF => 'PDF Document',
            self::EXCEL => 'Excel Spreadsheet',
            self::CSV => 'CSV File',
            self::JSON => 'JSON Data',
            self::XML => 'XML Document',
            self::HTML => 'HTML Page',
            self::TXT => 'Plain Text',
            self::PRINTER => 'Print Output',
        };
    }
}
