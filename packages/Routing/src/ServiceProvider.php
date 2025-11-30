<?php

declare(strict_types=1);

namespace Nexus\Routing;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nexus\Routing\Contracts\ConstraintValidatorInterface;
use Nexus\Routing\Services\ConstraintValidator;

/**
 * Service provider for Nexus\Routing package
 * 
 * Registers default implementations for framework-agnostic interfaces
 */
class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register stateless constraint validator
        $this->app->singleton(ConstraintValidatorInterface::class, ConstraintValidator::class);

        // Note: RouteOptimizerInterface and RouteCacheInterface must be bound
        // in the application (Atomy) as they may require configuration or
        // specific implementations (TspOptimizer vs VrpOptimizer vs OR-Tools)
    }
}
