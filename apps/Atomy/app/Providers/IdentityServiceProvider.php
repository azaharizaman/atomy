<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LaravelAuthContext;
use App\Services\LaravelSessionManager;
use App\Repositories\DbTrustedDeviceRepository;
use App\Factories\TrustedDeviceFactory;
use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TrustedDeviceRepositoryInterface;
use Nexus\Identity\Contracts\TrustedDeviceFactoryInterface;
use Nexus\Identity\Services\TrustedDeviceManager;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\HasherInterface;

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

        // Bind SessionManagerInterface with Crypto dependencies
        $this->app->singleton(SessionManagerInterface::class, function ($app) {
            return new LaravelSessionManager(
                $app->make(KeyGeneratorInterface::class),
                $app->make(HasherInterface::class)
            );
        });

        // Bind TrustedDeviceRepositoryInterface
        $this->app->singleton(
            TrustedDeviceRepositoryInterface::class,
            DbTrustedDeviceRepository::class
        );

        // Bind TrustedDeviceFactoryInterface
        $this->app->singleton(
            TrustedDeviceFactoryInterface::class,
            TrustedDeviceFactory::class
        );

        // Bind TrustedDeviceManager
        $this->app->singleton(TrustedDeviceManager::class);
    }

    public function boot(): void
    {
        // No boot actions needed yet
    }
}
