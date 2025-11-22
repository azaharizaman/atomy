<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\Channels\SmsChannel;
use App\Services\LaravelCacheAdapter;
use App\Services\LaravelTokenManager;
use App\Repositories\DbRoleRepository;
use App\Repositories\DbUserRepository;
use App\Services\Channels\PushChannel;
use App\Services\NotificationRenderer;
use App\Services\Channels\EmailChannel;
use App\Services\Channels\InAppChannel;
use App\Services\LaravelPasswordHasher;
use App\Services\LaravelSessionManager;
use Illuminate\Support\ServiceProvider;
// use Nexus\Identity\Services\RoleManager; // TODO: Uncomment when RoleManagerInterface exists
// use Nexus\Identity\Services\UserManager; // TODO: Uncomment when UserManagerInterface exists
use Nexus\Period\Services\PeriodManager;
use App\Repositories\DbNotificationQueue;
use App\Services\LaravelPasswordValidator;
use App\Services\LaravelUserAuthenticator;
use App\Services\PeriodAuditLoggerAdapter;
use App\Repositories\DbPermissionRepository;
use App\Services\PeriodAuthorizationService;
use App\Repositories\EloquentPeriodRepository;
use Nexus\Identity\Services\PermissionChecker;
// use Nexus\Identity\Services\PermissionManager; // TODO: Uncomment when PermissionManagerInterface exists
use Nexus\Notifier\Services\NotificationManager;
use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\Notifier\Contracts\SmsChannelInterface;
// use Nexus\Identity\Contracts\RoleManagerInterface; // TODO: Interface not implemented yet
// use Nexus\Identity\Contracts\UserManagerInterface; // TODO: Interface not implemented yet
use Nexus\Identity\Services\AuthenticationService;
use Nexus\Notifier\Contracts\PushChannelInterface;
use Nexus\Period\Contracts\AuthorizationInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Notifier\Contracts\EmailChannelInterface;
use Nexus\Notifier\Contracts\InAppChannelInterface;
use Nexus\Period\Contracts\CacheRepositoryInterface;
use App\Repositories\DbNotificationHistoryRepository;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use App\Repositories\DbNotificationTemplateRepository;
use App\Repositories\DbNotificationPreferenceRepository;
use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
// use Nexus\Identity\Contracts\PermissionManagerInterface; // TODO: Interface not implemented yet
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Notifier\Contracts\NotificationQueueInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationRendererInterface;
use Nexus\Notifier\Contracts\NotificationHistoryRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationTemplateRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationPreferenceRepositoryInterface;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use App\Repositories\EloquentAccountRepository;
use App\Repositories\EloquentJournalEntryRepository;
use App\Repositories\EloquentLedgerRepository;
use Nexus\Finance\Services\FinanceManager;
use Nexus\Accounting\Contracts\StatementRepositoryInterface;
use Nexus\Accounting\Contracts\StatementBuilderInterface;
use Nexus\Accounting\Contracts\PeriodCloseServiceInterface;
use Nexus\Accounting\Contracts\ConsolidationEngineInterface;
// use Nexus\Accounting\Contracts\VarianceCalculatorInterface; // TODO: Interface not implemented yet
use Nexus\Accounting\Services\AccountingManager;
use Nexus\Accounting\Core\Engine\StatementBuilder;
use Nexus\Accounting\Core\Engine\PeriodCloseService;
use Nexus\Accounting\Core\Engine\ConsolidationEngine;
// use Nexus\Accounting\Core\Engine\VarianceCalculator; // TODO: Uncomment when interface exists
use App\Repositories\EloquentStatementRepository;
use Nexus\Sales\Contracts\QuotationRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\PriceListRepositoryInterface;
use Nexus\Sales\Contracts\TaxCalculatorInterface;
use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Sales\Contracts\SalesReturnInterface;
use App\Repositories\DbQuotationRepository;
use App\Repositories\DbSalesOrderRepository;
use App\Repositories\DbPriceListRepository;
use Nexus\Sales\Services\SimpleTaxCalculator;
use Nexus\Sales\Services\NoOpCreditLimitChecker;
use Nexus\Sales\Services\StubInvoiceManager;
use Nexus\Sales\Services\StubStockReservation;
use Nexus\Sales\Services\StubSalesReturnManager;
use Nexus\Sales\Services\PricingEngine;
use Nexus\Sales\Services\QuotationManager;
use Nexus\Sales\Services\SalesOrderManager;
use Nexus\Sales\Services\QuoteToOrderConverter;
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\Contracts\GeofenceInterface;
use Nexus\Geo\Contracts\PolygonSimplifierInterface;
use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\Contracts\BearingCalculatorInterface;
use Nexus\Geo\Contracts\TravelTimeInterface;
use App\Repositories\DbGeoRepository;
use App\Services\LaravelGeocoder;
use App\Services\LaravelGeofence;
use Nexus\Geo\Services\PolygonSimplifier;
use Nexus\Geo\Services\DistanceCalculator;
use Nexus\Geo\Services\BearingCalculator;
use Nexus\Geo\Services\TravelTimeEstimator;
use Nexus\Routing\Contracts\RouteCacheInterface;
use App\Repositories\DbRouteCacheRepository;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use App\Repositories\Infrastructure\EloquentEventStore;
use App\Repositories\Infrastructure\EloquentSnapshotRepository;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Identity Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(UserRepositoryInterface::class, DbUserRepository::class);
        $this->app->singleton(RoleRepositoryInterface::class, DbRoleRepository::class);
        $this->app->singleton(PermissionRepositoryInterface::class, DbPermissionRepository::class);

        // Laravel Services (Essential - Interface to Laravel Implementation)
        $this->app->singleton(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->singleton(PasswordValidatorInterface::class, LaravelPasswordValidator::class);
        $this->app->singleton(UserAuthenticatorInterface::class, LaravelUserAuthenticator::class);
        $this->app->singleton(SessionManagerInterface::class, LaravelSessionManager::class);
        $this->app->singleton(TokenManagerInterface::class, LaravelTokenManager::class);

        // Package Services (Essential - Interface to Package Default)
        // TODO: Uncomment when interfaces are implemented in Identity package
        // $this->app->singleton(UserManagerInterface::class, UserManager::class);
        // $this->app->singleton(RoleManagerInterface::class, RoleManager::class);
        // $this->app->singleton(PermissionManagerInterface::class, PermissionManager::class);
        $this->app->singleton(PermissionCheckerInterface::class, PermissionChecker::class);
        $this->app->singleton(AuthenticationService::class);

        // Notifier Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(NotificationTemplateRepositoryInterface::class, DbNotificationTemplateRepository::class);
        $this->app->singleton(NotificationHistoryRepositoryInterface::class, DbNotificationHistoryRepository::class);
        $this->app->singleton(NotificationPreferenceRepositoryInterface::class, DbNotificationPreferenceRepository::class);
        $this->app->singleton(NotificationQueueInterface::class, DbNotificationQueue::class);

        // Renderer (Essential - Interface to Concrete)
        $this->app->singleton(NotificationRendererInterface::class, NotificationRenderer::class);

        // Package Services (Essential - Interface to Package Default)
        $this->app->singleton(NotificationManagerInterface::class, function ($app) {
            return new NotificationManager(
                channels: [
                    $app->make(EmailChannel::class),
                    $app->make(SmsChannel::class),
                    $app->make(PushChannel::class),
                    $app->make(InAppChannel::class),
                ],
                queue: $app->make(NotificationQueueInterface::class),
                history: $app->make(NotificationHistoryRepositoryInterface::class),
                preferences: $app->make(NotificationPreferenceRepositoryInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Period Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(PeriodRepositoryInterface::class, EloquentPeriodRepository::class);

        // Application Services (Essential - Interface to Concrete)
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheAdapter::class);
        $this->app->singleton(AuthorizationInterface::class, PeriodAuthorizationService::class);
        $this->app->singleton(AuditLoggerInterface::class, PeriodAuditLoggerAdapter::class);

        // Package Services (Essential - Interface to Package Default)
        $this->app->singleton(PeriodManagerInterface::class, PeriodManager::class);

        // Finance Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->singleton(JournalEntryRepositoryInterface::class, EloquentJournalEntryRepository::class);
        $this->app->singleton(LedgerRepositoryInterface::class, EloquentLedgerRepository::class);

        // Package Services (Essential - Interface to Package Default)
        $this->app->singleton(FinanceManagerInterface::class, FinanceManager::class);

        // Accounting Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(StatementRepositoryInterface::class, EloquentStatementRepository::class);

        // Core Engines (Essential - Interface to Package Default)
        $this->app->singleton(StatementBuilderInterface::class, StatementBuilder::class);
        $this->app->singleton(PeriodCloseServiceInterface::class, PeriodCloseService::class);
        $this->app->singleton(ConsolidationEngineInterface::class, ConsolidationEngine::class);
        // TODO: Uncomment when VarianceCalculatorInterface is implemented in Accounting package
        // $this->app->singleton(VarianceCalculatorInterface::class, VarianceCalculator::class);

        // Package Services (Essential - Singleton)
        $this->app->singleton(AccountingManager::class);

        // Sales Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(QuotationRepositoryInterface::class, DbQuotationRepository::class);
        $this->app->singleton(SalesOrderRepositoryInterface::class, DbSalesOrderRepository::class);
        $this->app->singleton(PriceListRepositoryInterface::class, DbPriceListRepository::class);

        // Tax Calculator (V1 - Simple flat rate implementation)
        $this->app->singleton(TaxCalculatorInterface::class, function ($app) {
            return new SimpleTaxCalculator(
                logger: $app->make(\Psr\Log\LoggerInterface::class),
                defaultTaxRate: 6.0 // 6% SST for Malaysia
            );
        });

        // Credit Limit Checker (V1 - No-op stub, always approves)
        $this->app->singleton(CreditLimitCheckerInterface::class, function ($app) {
            return new NoOpCreditLimitChecker(
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Invoice Manager (V1 - Stub, throws exception)
        $this->app->singleton(InvoiceManagerInterface::class, StubInvoiceManager::class);

        // Stock Reservation (V1 - Stub, throws exception)
        $this->app->singleton(StockReservationInterface::class, StubStockReservation::class);

        // Sales Return Manager (V1 - Stub, throws exception)
        $this->app->singleton(SalesReturnInterface::class, StubSalesReturnManager::class);

        // Package Services (Essential - Singletons)
        $this->app->singleton(PricingEngine::class);
        $this->app->singleton(QuotationManager::class);
        $this->app->singleton(SalesOrderManager::class);
        $this->app->singleton(QuoteToOrderConverter::class);

        // Geo Package Bindings

        // Repositories (Essential - Interface to Concrete)
        $this->app->singleton(GeoRepositoryInterface::class, DbGeoRepository::class);

        // Geocoder (Essential - Interface to Laravel Implementation with Google Maps + Nominatim)
        $this->app->singleton(GeocoderInterface::class, function ($app) {
            return new LaravelGeocoder(
                connectorManager: $app->make(\Nexus\Connector\Services\ConnectorManager::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
                googleMapsApiKey: config('geo.google_maps_api_key'),
                nominatimUserAgent: config('geo.nominatim_user_agent', 'Nexus/1.0')
            );
        });

        // Geofence (Essential - Interface to Laravel Implementation)
        $this->app->singleton(GeofenceInterface::class, LaravelGeofence::class);

        // Polygon Simplifier (Essential - Interface to Package Default)
        $this->app->singleton(PolygonSimplifierInterface::class, PolygonSimplifier::class);

        // Distance Calculator (Essential - Interface to Package Default)
        $this->app->singleton(DistanceCalculatorInterface::class, DistanceCalculator::class);

        // Bearing Calculator (Essential - Interface to Package Default)
        $this->app->singleton(BearingCalculatorInterface::class, BearingCalculator::class);

        // Travel Time Estimator (Essential - Interface to Package Default)
        $this->app->singleton(TravelTimeInterface::class, TravelTimeEstimator::class);

        // Routing Package Bindings

        // Route Cache (Essential - Interface to Concrete)
        $this->app->singleton(RouteCacheInterface::class, DbRouteCacheRepository::class);

        // EventStream Package Bindings

        // Event Store (Essential - Interface to Concrete)
        $this->app->singleton(EventStoreInterface::class, EloquentEventStore::class);
        $this->app->singleton(StreamReaderInterface::class, EloquentEventStore::class);

        // Snapshot Repository (Essential - Interface to Concrete)
        $this->app->singleton(SnapshotRepositoryInterface::class, EloquentSnapshotRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
