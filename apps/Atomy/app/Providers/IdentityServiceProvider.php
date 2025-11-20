<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LaravelAuthContext;
use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\AuthContextInterface;

/**
 * Identity service provider
 * 
 * Binds Identity package contracts to Laravel implementations
 */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind AuthContextInterface to Laravel implementation
        $this->app->scoped(
            AuthContextInterface::class,
            LaravelAuthContext::class
        );
    }

    public function boot(): void
    {
        // No boot actions needed yet
    }
}
