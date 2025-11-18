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
use Nexus\Identity\Services\RoleManager;
use Nexus\Identity\Services\UserManager;
use Nexus\Period\Services\PeriodManager;
use App\Repositories\DbNotificationQueue;
use App\Services\LaravelPasswordValidator;
use App\Services\LaravelUserAuthenticator;
use App\Services\PeriodAuditLoggerAdapter;
use App\Repositories\DbPermissionRepository;
use App\Services\PeriodAuthorizationService;
use App\Repositories\EloquentPeriodRepository;
use Nexus\Identity\Services\PermissionChecker;
use Nexus\Identity\Services\PermissionManager;
use Nexus\Notifier\Services\NotificationManager;
use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\Notifier\Contracts\SmsChannelInterface;
use Nexus\Identity\Contracts\RoleManagerInterface;
use Nexus\Identity\Contracts\UserManagerInterface;
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
use Nexus\Identity\Contracts\PermissionManagerInterface;
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
use Nexus\Accounting\Contracts\VarianceCalculatorInterface;
use Nexus\Accounting\Services\AccountingManager;
use Nexus\Accounting\Core\Engine\StatementBuilder;
use Nexus\Accounting\Core\Engine\PeriodCloseService;
use Nexus\Accounting\Core\Engine\ConsolidationEngine;
use Nexus\Accounting\Core\Engine\VarianceCalculator;
use App\Repositories\EloquentStatementRepository;

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
        $this->app->singleton(UserManagerInterface::class, UserManager::class);
        $this->app->singleton(RoleManagerInterface::class, RoleManager::class);
        $this->app->singleton(PermissionManagerInterface::class, PermissionManager::class);
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
        $this->app->singleton(VarianceCalculatorInterface::class, VarianceCalculator::class);

        // Package Services (Essential - Singleton)
        $this->app->singleton(AccountingManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
