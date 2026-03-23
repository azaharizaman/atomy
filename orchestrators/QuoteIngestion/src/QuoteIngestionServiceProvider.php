<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use Illuminate\Support\ServiceProvider;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;

class QuoteIngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuoteIngestionOrchestrator::class, function ($app) {
            return new QuoteIngestionOrchestrator(
                $app->make(QuoteExtractionServiceInterface::class),
            );
        });
    }
}
