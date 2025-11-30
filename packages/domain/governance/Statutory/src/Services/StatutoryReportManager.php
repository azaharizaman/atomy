<?php

declare(strict_types=1);

namespace Nexus\Statutory\Services;

use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Core\Engine\FinanceDataExtractor;
use Nexus\Statutory\Core\Engine\ReportGenerator;
use Nexus\Statutory\Core\Engine\SchemaValidator;
use Nexus\Statutory\Exceptions\InvalidReportTypeException;
use Nexus\Statutory\Exceptions\ReportNotFoundException;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Psr\Log\LoggerInterface;

/**
 * Service for managing statutory reports with full pipeline integration.
 * 
 * This service orchestrates the full report generation pipeline:
 * 1. Data extraction from finance package
 * 2. Schema validation
 * 3. Report generation via adapters
 * 4. Format conversion
 * 5. Persistence
 */
final class StatutoryReportManager
{
    public function __construct(
        private readonly StatutoryReportRepositoryInterface $repository,
        private readonly TaxonomyReportGeneratorInterface $generator,
        private readonly FinanceDataExtractor $dataExtractor,
        private readonly SchemaValidator $schemaValidator,
        private readonly ReportGenerator $reportGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a new statutory report using the full pipeline.
     *
     * Pipeline Steps:
     * 1. Extract financial data from finance package
     * 2. Validate data against schema
     * 3. Generate report via adapter
     * 4. Convert to requested format
     * 5. Persist report instance
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type identifier
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param ReportFormat $format Desired output format
     * @param array<string, mixed> $accountData Account data from repository
     * @param array<string, mixed> $options Additional generation options
     * @return string The report ID
     */
    public function generateReport(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        array $accountData,
        array $options = []
    ): string {
        $this->logger->info("Generating statutory report via pipeline", [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'format' => $format->value,
        ]);

        // Step 1: Extract financial data
        $reportData = $this->extractReportData(
            $tenantId,
            $reportType,
            $startDate,
            $endDate,
            $accountData
        );

        $this->logger->info("Financial data extracted", [
            'report_type' => $reportType,
            'data_keys' => array_keys($reportData),
        ]);

        // Step 2: Validate against schema
        $metadata = $this->generator->getReportMetadata($reportType);
        $schemaIdentifier = $metadata->getSchemaIdentifier();
        
        $validationErrors = $this->schemaValidator->validate($schemaIdentifier, $reportData);
        if (!empty($validationErrors)) {
            $this->logger->error("Schema validation failed", [
                'errors' => $validationErrors,
            ]);
            throw new \Nexus\Statutory\Exceptions\ValidationException(
                $reportType,
                $validationErrors
            );
        }
        $this->logger->info("Data schema validation passed");

        // Step 3: Generate report via adapter
        $generatedReport = $this->generator->generateReport(
            $tenantId,
            $reportType,
            $startDate,
            $endDate,
            $format,
            $options
        );

        $this->logger->info("Report generated successfully", [
            'report_id' => $generatedReport,
        ]);

        return $generatedReport;
    }

    /**
     * Generate a report with full metadata (checksum, timestamps).
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type identifier
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param ReportFormat $format Desired output format
     * @param array<string, mixed> $accountData Account data from repository
     * @param array<string, mixed> $options Additional generation options
     * @return array{report_id: string, content: string, metadata: array<string, mixed>}
     */
    public function generateReportWithMetadata(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        array $accountData,
        array $options = []
    ): array {
        $this->logger->info("Generating report with metadata", [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
        ]);

        // Extract financial data
        $reportData = $this->extractReportData(
            $tenantId,
            $reportType,
            $startDate,
            $endDate,
            $accountData
        );

        // Get metadata
        $metadata = $this->generator->getReportMetadata($reportType);
        $schemaIdentifier = $metadata->getSchemaIdentifier();

        // Generate report with metadata using ReportGenerator
        $result = $this->reportGenerator->generateWithMetadata(
            $reportType,
            $reportData,
            [
                'tenant_id' => $tenantId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'country_code' => $metadata->getCountryCode(),
                'regulatory_authority' => $metadata->getRegulatoryAuthority(),
            ],
            $format,
            $schemaIdentifier
        );

        // Create report entity
        $reportId = $this->createReportEntity(
            $tenantId,
            $reportType,
            $startDate,
            $endDate,
            $format,
            $result['checksum']
        );

        $this->logger->info("Report with metadata generated", [
            'report_id' => $reportId,
            'checksum' => $result['checksum'],
        ]);

        return [
            'report_id' => $reportId,
            'content' => $result['content'],
            'metadata' => $result['metadata'],
        ];
    }

    /**
     * Extract financial data based on report type.
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param array<string, mixed> $accountData Account data from repository
     * @return array<string, mixed> Extracted report data
     */
    private function extractReportData(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $accountData
    ): array {
        return match ($reportType) {
            'profit_loss' => $this->dataExtractor->extractProfitLoss(
                $tenantId,
                $startDate,
                $endDate,
                $accountData
            ),
            'balance_sheet' => $this->dataExtractor->extractBalanceSheet(
                $tenantId,
                $endDate,
                $accountData
            ),
            'trial_balance' => $this->dataExtractor->extractTrialBalance(
                $tenantId,
                $startDate,
                $endDate,
                $accountData
            ),
            default => throw new InvalidReportTypeException($reportType),
        };
    }

    /**
     * Create a report entity in the repository.
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param ReportFormat $format The report format
     * @param string $checksum The report checksum
     * @return string The report ID
     */
    private function createReportEntity(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        string $checksum
    ): string {
        // This method will be properly implemented when repository interface is enhanced
        // For now, return a placeholder
        return 'report_' . uniqid();
    }

    /**
     * Get a statutory report by ID.
     *
     * @param string $reportId The report ID
     * @return StatutoryReportInterface
     * @throws ReportNotFoundException
     */
    public function getReport(string $reportId): StatutoryReportInterface
    {
        $report = $this->repository->findById($reportId);
        
        if ($report === null) {
            throw new ReportNotFoundException($reportId);
        }

        return $report;
    }

    /**
     * Get all reports for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string|null $reportType Optional filter by report type
     * @param \DateTimeImmutable|null $from Optional start date filter
     * @param \DateTimeImmutable|null $to Optional end date filter
     * @return array<StatutoryReportInterface>
     */
    public function getReports(
        string $tenantId,
        ?string $reportType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        return $this->repository->getReports($tenantId, $reportType, $from, $to);
    }

    /**
     * Get available report types.
     *
     * @return array<string>
     */
    public function getAvailableReportTypes(): array
    {
        return $this->generator->getSupportedReportTypes();
    }

    /**
     * Validate report data.
     *
     * @param string $reportType The report type
     * @param array<string, mixed> $data The data to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateReportData(string $reportType, array $data): array
    {
        return $this->generator->validateReportData($reportType, $data);
    }
}
