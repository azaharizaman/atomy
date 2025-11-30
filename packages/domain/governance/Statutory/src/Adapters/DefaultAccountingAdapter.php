<?php

declare(strict_types=1);

namespace Nexus\Statutory\Adapters;

use Nexus\Statutory\Contracts\ReportMetadataInterface;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Exceptions\DataExtractionException;
use Nexus\Statutory\Exceptions\InvalidReportTypeException;
use Nexus\Statutory\Exceptions\ValidationException;
use Nexus\Statutory\ValueObjects\FilingFrequency;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Psr\Log\LoggerInterface;

/**
 * Default accounting report adapter for basic financial statements.
 * 
 * Provides basic P&L (Profit & Loss) and Balance Sheet reports in JSON/CSV format.
 * This is a reference implementation that can be extended for country-specific requirements.
 */
final class DefaultAccountingAdapter implements TaxonomyReportGeneratorInterface
{
    private const SUPPORTED_REPORTS = ['profit_loss', 'balance_sheet', 'trial_balance'];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function generateReport(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        array $options = []
    ): string {
        $this->logger->info("Generating default accounting report", [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'format' => $format->value,
        ]);

        if (!in_array($reportType, self::SUPPORTED_REPORTS, true)) {
            throw new InvalidReportTypeException($reportType);
        }

        // Validate format support
        if (!in_array($format, [ReportFormat::JSON, ReportFormat::CSV], true)) {
            throw new ValidationException($reportType, [
                "Default adapter only supports JSON and CSV formats, got: {$format->value}"
            ]);
        }

        // Report generation logic will be implemented in application layer
        // This is a placeholder for the service skeleton
        
        $this->logger->info("Default accounting report generated successfully", [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
        ]);

        return 'report-id-placeholder';
    }

    public function getReportMetadata(string $reportType): ReportMetadataInterface
    {
        if (!in_array($reportType, self::SUPPORTED_REPORTS, true)) {
            throw new InvalidReportTypeException($reportType);
        }

        return new class($reportType) implements ReportMetadataInterface {
            public function __construct(private readonly string $reportType)
            {
            }

            public function getReportIdentifier(): string
            {
                return "default_{$this->reportType}";
            }

            public function getReportName(): string
            {
                return match ($this->reportType) {
                    'profit_loss' => 'Profit & Loss Statement',
                    'balance_sheet' => 'Balance Sheet',
                    'trial_balance' => 'Trial Balance',
                    default => 'Unknown Report',
                };
            }

            public function getCountryCode(): string
            {
                return 'DEFAULT';
            }

            public function getRegulatoryAuthority(): string
            {
                return 'Internal';
            }

            public function getFilingFrequency(): FilingFrequency
            {
                return FilingFrequency::ON_DEMAND;
            }

            public function getSupportedFormats(): array
            {
                return [ReportFormat::JSON, ReportFormat::CSV];
            }

            public function getSchemaIdentifier(): string
            {
                return "json:default:{$this->reportType}:v1";
            }

            public function getSchemaVersion(): string
            {
                return 'v1.0';
            }

            public function getValidationRules(): array
            {
                return [];
            }

            public function requiresDigitalSignature(): bool
            {
                return false;
            }

            public function getRequiredFields(): array
            {
                return match ($this->reportType) {
                    'profit_loss' => ['revenue', 'expenses', 'net_income'],
                    'balance_sheet' => ['assets', 'liabilities', 'equity'],
                    'trial_balance' => ['account_code', 'debit', 'credit'],
                    default => [],
                };
            }
        };
    }

    public function validateReportData(string $reportType, array $data): array
    {
        if (!in_array($reportType, self::SUPPORTED_REPORTS, true)) {
            throw new InvalidReportTypeException($reportType);
        }

        $metadata = $this->getReportMetadata($reportType);
        $errors = [];

        foreach ($metadata->getRequiredFields() as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return $errors;
    }

    public function getSupportedReportTypes(): array
    {
        return self::SUPPORTED_REPORTS;
    }
}
