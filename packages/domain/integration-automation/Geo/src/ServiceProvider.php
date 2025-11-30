<?php

declare(strict_types=1);

namespace Nexus\Geo;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\Contracts\BearingCalculatorInterface;
use Nexus\Geo\Contracts\PolygonSimplifierInterface;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\BearingCalculator;
use Nexus\Geo\Services\PolygonSimplifier;

/**
 * Service provider for Nexus\Geo package
 * 
 * Registers default implementations for framework-agnostic interfaces
 */
class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register stateless services with default implementations
        $this->app->singleton(DistanceCalculatorInterface::class, DistanceCalculator::class);
        $this->app->singleton(BearingCalculatorInterface::class, BearingCalculator::class);
        $this->app->singleton(PolygonSimplifierInterface::class, PolygonSimplifier::class);

        // Note: GeocoderInterface, GeoRepositoryInterface, GeofenceInterface
        // must be bound in the application (Atomy) as they require framework-specific
        // implementations (database, HTTP clients, etc.)
    }
}
