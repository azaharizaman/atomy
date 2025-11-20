<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Party\EloquentAddressRepository;
use App\Repositories\Party\EloquentContactMethodRepository;
use App\Repositories\Party\EloquentPartyRelationshipRepository;
use App\Repositories\Party\EloquentPartyRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Party\Contracts\AddressRepositoryInterface;
use Nexus\Party\Contracts\ContactMethodRepositoryInterface;
use Nexus\Party\Contracts\PartyRelationshipRepositoryInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Services\PartyManager;
use Nexus\Party\Services\PartyRelationshipManager;
use Psr\Log\LoggerInterface;

final class PartyServiceProvider extends ServiceProvider
{
    /**
     * Register Party package services and bindings.
     */
    public function register(): void
    {
        // Bind repository interfaces to Eloquent implementations
        $this->app->singleton(PartyRepositoryInterface::class, EloquentPartyRepository::class);
        $this->app->singleton(AddressRepositoryInterface::class, EloquentAddressRepository::class);
        $this->app->singleton(ContactMethodRepositoryInterface::class, EloquentContactMethodRepository::class);
        $this->app->singleton(PartyRelationshipRepositoryInterface::class, EloquentPartyRelationshipRepository::class);

        // Bind PartyManager as singleton with all dependencies
        $this->app->singleton(PartyManager::class, function ($app) {
            return new PartyManager(
                partyRepository: $app->make(PartyRepositoryInterface::class),
                addressRepository: $app->make(AddressRepositoryInterface::class),
                contactMethodRepository: $app->make(ContactMethodRepositoryInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });

        // Bind PartyRelationshipManager as singleton with all dependencies
        $this->app->singleton(PartyRelationshipManager::class, function ($app) {
            return new PartyRelationshipManager(
                relationshipRepository: $app->make(PartyRelationshipRepositoryInterface::class),
                partyRepository: $app->make(PartyRepositoryInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap Party package services.
     */
    public function boot(): void
    {
        // No boot logic required for Party package
    }
}
