<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence;

use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteIngestionServiceInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Coordinators\QuotationIntelligenceCoordinator;
use Nexus\QuotationIntelligence\Services\QuoteIngestionService;
use Nexus\QuotationIntelligence\Services\AiSemanticMapper;
use Nexus\QuotationIntelligence\Services\QuoteNormalizationService;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for QuotationIntelligence orchestrator.
 */
class QuotationIntelligenceServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->app->singleton(QuoteIngestionServiceInterface::class, QuoteIngestionService::class);
        $this->app->singleton(SemanticMapperInterface::class, AiSemanticMapper::class);
        $this->app->singleton(QuoteNormalizationServiceInterface::class, QuoteNormalizationService::class);
        $this->app->singleton(RiskAssessmentServiceInterface::class, RuleBasedRiskAssessmentService::class);
        $this->app->singleton(QuotationIntelligenceCoordinatorInterface::class, QuotationIntelligenceCoordinator::class);
    }
}
