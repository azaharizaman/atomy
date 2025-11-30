<?php

declare(strict_types=1);

namespace Nexus\Statutory\Contracts;

use Nexus\Statutory\ValueObjects\FilingFrequency;
use Nexus\Statutory\ValueObjects\ReportFormat;

/**
 * Interface defining metadata for statutory reports.
 * 
 * All statutory report adapters must implement this interface to provide
 * metadata about the report structure, filing requirements, and validation rules.
 */
interface ReportMetadataInterface
{
    /**
     * Get the unique identifier for this report type.
     *
     * @return string Example: 'ssm_br', 'lhdn_pcb', 'kwsp_epf'
     */
    public function getReportIdentifier(): string;

    /**
     * Get the human-readable name of the report.
     *
     * @return string Example: 'SSM Business Registration', 'LHDN PCB Form'
     */
    public function getReportName(): string;

    /**
     * Get the country code for this statutory report.
     *
     * @return string ISO 3166-1 alpha-3 code (e.g., 'MYS', 'SGP', 'USA')
     */
    public function getCountryCode(): string;

    /**
     * Get the regulatory authority for this report.
     *
     * @return string Example: 'SSM', 'LHDN', 'KWSP', 'PERKESO'
     */
    public function getRegulatoryAuthority(): string;

    /**
     * Get the filing frequency for this report.
     *
     * @return FilingFrequency
     */
    public function getFilingFrequency(): FilingFrequency;

    /**
     * Get the supported output formats for this report.
     *
     * @return array<ReportFormat>
     */
    public function getSupportedFormats(): array;

    /**
     * Get the schema identifier for validation.
     *
     * @return string Example: 'xbrl:ssm-br:2024', 'json:lhdn-pcb:v1'
     */
    public function getSchemaIdentifier(): string;

    /**
     * Get the schema version.
     *
     * @return string Example: '2024.1', 'v1.0'
     */
    public function getSchemaVersion(): string;

    /**
     * Get the validation rules for this report.
     *
     * @return array<string, mixed> Validation rules configuration
     */
    public function getValidationRules(): array;

    /**
     * Check if digital signature is required for this report.
     *
     * @return bool
     */
    public function requiresDigitalSignature(): bool;

    /**
     * Get the list of required data fields for this report.
     *
     * @return array<string> Field identifiers
     */
    public function getRequiredFields(): array;
}
