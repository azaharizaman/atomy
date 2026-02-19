<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\PipelineQueryInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Pipeline Analytics Coordinator
 * 
 * Orchestrates pipeline analytics and reporting.
 * Coordinates between CRM and Analytics packages.
 * 
 * @package Nexus\CRMOperations\Coordinators
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class PipelineAnalyticsCoordinator
{
    /**
     * Default average days to close a deal
     */
    private const DEFAULT_AVERAGE_DAYS_TO_CLOSE = 45;

    /**
     * Default average days per stage
     */
    private const DEFAULT_AVERAGE_DAYS_PER_STAGE = 9;

    /**
     * Monthly revenue projection multiplier
     */
    private const MONTHLY_PROJECTION_MULTIPLIER = 0.3;

    /**
     * Quarterly revenue projection multiplier
     */
    private const QUARTERLY_PROJECTION_MULTIPLIER = 0.6;

    /**
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param PipelineQueryInterface $pipelineQuery Pipeline query service
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private OpportunityQueryInterface $opportunityQuery,
        private PipelineQueryInterface $pipelineQuery,
        private AnalyticsProviderInterface $analyticsProvider,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Get comprehensive pipeline dashboard data
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Dashboard data
     */
    public function getDashboardData(string $tenantId): array
    {
        $this->logger?->info('Generating pipeline dashboard', [
            'tenant_id' => $tenantId,
        ]);

        return [
            'summary' => $this->getPipelineSummary($tenantId),
            'by_stage' => $this->getOpportunitiesByStage($tenantId),
            'by_pipeline' => $this->getOpportunitiesByPipeline($tenantId),
            'trends' => $this->getPipelineTrends($tenantId),
            'forecasts' => $this->getForecasts($tenantId),
            'metrics' => $this->analyticsProvider->getPipelineMetrics($tenantId),
        ];
    }

    /**
     * Get pipeline summary statistics
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Summary data
     */
    public function getPipelineSummary(string $tenantId): array
    {
        $totalValue = $this->opportunityQuery->getTotalOpenValue();
        $weightedValue = $this->opportunityQuery->getWeightedPipelineValue();

        return [
            'total_opportunities' => $this->countOpenOpportunities($tenantId),
            'total_value' => $totalValue,
            'weighted_value' => $weightedValue,
            'average_deal_size' => $this->calculateAverageDealSize($tenantId),
            'pipeline_count' => $this->pipelineQuery->count(),
        ];
    }

    /**
     * Get opportunities grouped by stage
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Opportunities by stage
     */
    public function getOpportunitiesByStage(string $tenantId): array
    {
        $stages = [];
        
        foreach (\Nexus\CRM\Enums\OpportunityStage::openStages() as $stage) {
            $stages[$stage->value] = [
                'label' => $stage->label(),
                'count' => $this->opportunityQuery->countByStage($stage),
                'probability' => $stage->getDefaultProbability(),
            ];
        }

        return $stages;
    }

    /**
     * Get opportunities grouped by pipeline
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Opportunities by pipeline
     */
    public function getOpportunitiesByPipeline(string $tenantId): array
    {
        $pipelines = [];
        
        foreach ($this->pipelineQuery->findActive() as $pipeline) {
            $pipelines[$pipeline->getId()] = [
                'name' => $pipeline->getName(),
                'stage_count' => $pipeline->getStageCount(),
            ];
        }

        return $pipelines;
    }

    /**
     * Get pipeline trends over time
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Trend data
     */
    public function getPipelineTrends(string $tenantId): array
    {
        $conversionRates = $this->analyticsProvider->getConversionRates(
            $tenantId,
            new \DateTimeImmutable('-30 days'),
            new \DateTimeImmutable()
        );

        return [
            'conversion_rates' => $conversionRates,
            'velocity' => $this->calculatePipelineVelocity($tenantId),
        ];
    }

    /**
     * Get sales forecasts
     * 
     * @param string $tenantId Tenant ID
     * @return array<string, mixed> Forecast data
     */
    public function getForecasts(string $tenantId): array
    {
        return [
            'accuracy' => $this->analyticsProvider->getForecastAccuracy($tenantId),
            'projected' => $this->calculateProjectedRevenue($tenantId),
        ];
    }

    /**
     * Generate pipeline report
     * 
     * @param string $tenantId Tenant ID
     * @param string $reportType Report type
     * @param array<string, mixed> $parameters Report parameters
     * @return array<string, mixed> Report data
     */
    public function generateReport(
        string $tenantId,
        string $reportType,
        array $parameters = []
    ): array {
        $this->logger?->info('Generating pipeline report', [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
        ]);

        return $this->analyticsProvider->generateReport($reportType, array_merge(
            ['tenant_id' => $tenantId],
            $parameters
        ));
    }

    /**
     * Export pipeline data
     * 
     * @param string $tenantId Tenant ID
     * @param string $format Export format
     * @param array<string, mixed> $filters Export filters
     * @return string Export URL or data
     */
    public function exportData(
        string $tenantId,
        string $format = 'csv',
        array $filters = []
    ): string {
        $this->logger?->info('Exporting pipeline data', [
            'tenant_id' => $tenantId,
            'format' => $format,
        ]);

        return $this->analyticsProvider->exportData($format, array_merge(
            ['tenant_id' => $tenantId],
            $filters
        ));
    }

    /**
     * Count open opportunities
     */
    private function countOpenOpportunities(string $tenantId): int
    {
        $count = 0;
        foreach (\Nexus\CRM\Enums\OpportunityStage::openStages() as $stage) {
            $count += $this->opportunityQuery->countByStage($stage);
        }
        return $count;
    }

    /**
     * Calculate average deal size
     */
    private function calculateAverageDealSize(string $tenantId): int
    {
        $totalValue = $this->opportunityQuery->getTotalOpenValue();
        $count = $this->countOpenOpportunities($tenantId);

        return $count > 0 ? (int) round($totalValue / $count) : 0;
    }

    /**
     * Calculate pipeline velocity
     */
    private function calculatePipelineVelocity(string $tenantId): array
    {
        $this->logger?->debug('Calculating pipeline velocity', ['tenant_id' => $tenantId]);
        
        // Calculate actual velocity based on closed opportunities
        $totalDaysToClose = 0;
        $closedCount = 0;
        
        foreach ($this->opportunityQuery->findWon() as $opportunity) {
            $createdAt = $opportunity->getCreatedAt();
            $closedAt = $opportunity->getActualCloseDate();
            
            if ($closedAt !== null) {
                $totalDaysToClose += $closedAt->getTimestamp() - $createdAt->getTimestamp();
                $closedCount++;
            }
        }
        
        $averageDaysToClose = $closedCount > 0 
            ? (int) round($totalDaysToClose / $closedCount / 86400) 
            : self::DEFAULT_AVERAGE_DAYS_TO_CLOSE;
        
        // Calculate average days per stage
        $stageCount = iterator_count($this->pipelineQuery->findActive());
        $averageDaysPerStage = $stageCount > 0 
            ? (int) round($averageDaysToClose / $stageCount) 
            : self::DEFAULT_AVERAGE_DAYS_PER_STAGE;

        return [
            'average_days_to_close' => $averageDaysToClose,
            'average_days_per_stage' => $averageDaysPerStage,
        ];
    }

    /**
     * Calculate projected revenue
     */
    private function calculateProjectedRevenue(string $tenantId): array
    {
        $weightedValue = $this->opportunityQuery->getWeightedPipelineValue();
        
        return [
            'this_month' => (int) round($weightedValue * self::MONTHLY_PROJECTION_MULTIPLIER),
            'this_quarter' => (int) round($weightedValue * self::QUARTERLY_PROJECTION_MULTIPLIER),
            'this_year' => $weightedValue,
        ];
    }
}