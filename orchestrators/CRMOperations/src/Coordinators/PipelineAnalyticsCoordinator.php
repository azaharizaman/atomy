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
        $totalValue = $this->opportunityQuery->getTotalOpenValue($tenantId);
        $weightedValue = $this->opportunityQuery->getWeightedPipelineValue($tenantId);

        return [
            'total_opportunities' => $this->countOpenOpportunities($tenantId),
            'total_value' => $totalValue,
            'weighted_value' => $weightedValue,
            'average_deal_size' => $this->calculateAverageDealSize($tenantId),
            'pipeline_count' => $this->pipelineQuery->count($tenantId),
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
                'count' => $this->opportunityQuery->countByStage($stage, $tenantId),
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
        
        foreach ($this->pipelineQuery->findActive($tenantId) as $pipeline) {
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
            $count += $this->opportunityQuery->countByStage($stage, $tenantId);
        }
        return $count;
    }

    /**
     * Calculate average deal size
     */
    private function calculateAverageDealSize(string $tenantId): int
    {
        $totalValue = $this->opportunityQuery->getTotalOpenValue($tenantId);
        $count = $this->countOpenOpportunities($tenantId);

        return $count > 0 ? (int) round($totalValue / $count) : 0;
    }

    /**
     * Calculate pipeline velocity
     */
    private function calculatePipelineVelocity(string $tenantId): array
    {
        // Placeholder for velocity calculation
        return [
            'average_days_to_close' => 45,
            'average_days_per_stage' => 9,
        ];
    }

    /**
     * Calculate projected revenue
     */
    private function calculateProjectedRevenue(string $tenantId): array
    {
        $weightedValue = $this->opportunityQuery->getWeightedPipelineValue($tenantId);
        
        return [
            'this_month' => (int) round($weightedValue * 0.3),
            'this_quarter' => (int) round($weightedValue * 0.6),
            'this_year' => $weightedValue,
        ];
    }
}