<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Document format enumeration for rendering.
 *
 * Defines the output formats supported by ContentProcessorInterface.
 */
enum DocumentFormat: string
{
    case PDF = 'pdf';
    case HTML = 'html';
    case DOCX = 'docx';
    case XLSX = 'xlsx';
    case CSV = 'csv';

    /**
     * Get the MIME type for the format.
     */
    public function mimeType(): string
    {
        return match ($this) {
            self::PDF => 'application/pdf',
            self::HTML => 'text/html',
            self::DOCX => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
        };
    }

    /**
     * Get the file extension for the format.
     */
    public function extension(): string
    {
        return $this->value;
    }
}
