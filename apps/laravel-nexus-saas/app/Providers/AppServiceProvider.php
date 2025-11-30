<?php

namespace App\Providers;

use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Repositories\FeatureFlagRepository;
use App\Repositories\UserFlagOverrideRepository;
use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Contracts\PercentageHasherInterface;
use Nexus\FeatureFlags\Services\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\Services\PercentageHasher;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Identity bindings
        $this->app->singleton(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // Tenant context binding
        $this->app->singleton(TenantContextInterface::class, TenantContext::class);

        // FeatureFlags package bindings
        $this->app->singleton(PercentageHasherInterface::class, PercentageHasher::class);
        $this->app->singleton(FlagRepositoryInterface::class, FeatureFlagRepository::class);
        $this->app->singleton(FlagEvaluatorInterface::class, DefaultFlagEvaluator::class);
        $this->app->singleton(FeatureFlagManagerInterface::class, FeatureFlagManager::class);

        // Application-specific repositories
        $this->app->singleton(FeatureFlagRepository::class);
        $this->app->singleton(UserFlagOverrideRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
