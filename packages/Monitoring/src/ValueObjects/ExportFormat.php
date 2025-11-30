<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

/**
 * Export Format Enumeration
 *
 * Defines supported metric export formats for external monitoring tools.
 *
 * @package Nexus\Monitoring\ValueObjects
 */
enum ExportFormat: string
{
    /**
     * Prometheus Exposition Format
     * Standard text-based format for Prometheus scraping
     */
    case PROMETHEUS = 'prometheus';

    /**
     * OpenMetrics Format
     * Cloud Native Computing Foundation standard
     */
    case OPENMETRICS = 'openmetrics';

    /**
     * JSON Format
     * Human-readable JSON structure
     */
    case JSON = 'json';

    /**
     * Get MIME type for this export format.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::PROMETHEUS => 'text/plain; version=0.0.4',
            self::OPENMETRICS => 'application/openmetrics-text; version=1.0.0',
            self::JSON => 'application/json',
        };
    }

    /**
     * Get file extension for this format.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return match ($this) {
            self::PROMETHEUS, self::OPENMETRICS => 'txt',
            self::JSON => 'json',
        };
    }
}
