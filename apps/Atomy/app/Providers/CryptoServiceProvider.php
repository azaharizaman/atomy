<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use App\Services\LaravelKeyStorage;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Handlers\KeyRotationHandler;
use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\Services\NativeHasher;
use Nexus\Crypto\Services\SodiumEncryptor;
use Nexus\Crypto\Services\SodiumSigner;

/**
 * Crypto Service Provider
 *
 * Binds Nexus\Crypto interfaces to concrete implementations.
 * Registers key rotation handler for Scheduler integration.
 */
class CryptoServiceProvider extends ServiceProvider
{
    /**
     * Register crypto services
     */
    public function register(): void
    {
        // Bind core crypto interfaces
        $this->app->singleton(HasherInterface::class, NativeHasher::class);
        $this->app->singleton(SymmetricEncryptorInterface::class, SodiumEncryptor::class);
        $this->app->singleton(AsymmetricSignerInterface::class, SodiumSigner::class);
        $this->app->singleton(KeyGeneratorInterface::class, KeyGenerator::class);
        
        // Bind key storage (Laravel-specific)
        $this->app->singleton(KeyStorageInterface::class, LaravelKeyStorage::class);
        
        // Bind CryptoManager facade
        $this->app->singleton(CryptoManager::class, function ($app) {
            return new CryptoManager(
                hasher: $app->make(HasherInterface::class),
                encryptor: $app->make(SymmetricEncryptorInterface::class),
                signer: $app->make(AsymmetricSignerInterface::class),
                keyGenerator: $app->make(KeyGeneratorInterface::class),
                keyStorage: $app->make(KeyStorageInterface::class),
                logger: $app->make(LoggerInterface::class),
            );
        });
        
        // Register key rotation handler for Scheduler
        $this->app->singleton(KeyRotationHandler::class, function ($app) {
            return new KeyRotationHandler(
                cryptoManager: $app->make(CryptoManager::class),
                logger: $app->make(LoggerInterface::class),
            );
        });
    }
    
    /**
     * Bootstrap crypto services
     */
    public function boot(): void
    {
        // Tag key rotation handler for Scheduler discovery
        if ($this->app->has('scheduler.handlers.tag')) {
            $this->app->tag([KeyRotationHandler::class], 'scheduler.handlers');
        }
        
        // Publish config if running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/crypto.php' => config_path('crypto.php'),
            ], 'crypto-config');
        }
    }
}
