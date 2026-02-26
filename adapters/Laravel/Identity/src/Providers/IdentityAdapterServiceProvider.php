<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Laravel\Identity\Adapters\CacheRepositoryAdapter;
use Nexus\Laravel\Identity\Adapters\PermissionCheckerAdapter;

/**
 * Laravel Service Provider for Identity package adapters.
 */
class IdentityAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache repository adapter
        $this->app->singleton(CacheRepositoryInterface::class, function ($app) {
            return new CacheRepositoryAdapter(
                cache: $app['cache.store'],
                logger: $app['log']
            );
        });

        // Register permission checker adapter
        $this->app->singleton(PermissionCheckerInterface::class, function ($app) {
            return new PermissionCheckerAdapter(
                logger: $app['log']
            );
        });

        // Register transaction manager
        $this->app->singleton(\Nexus\IdentityOperations\Contracts\TransactionManagerInterface::class, function ($app) {
            return new \Nexus\Laravel\Identity\Adapters\LaravelTransactionManager();
        });

        // Register IdentityOperations orchestrator adapter
        $this->registerIdentityOperationsAdapters();
    }

    /**
     * Register adapters for IdentityOperations orchestrator.
     */
    private function registerIdentityOperationsAdapters(): void
    {
        $this->app->singleton(\Nexus\Laravel\Identity\Adapters\IdentityOperationsAdapter::class, function ($app) {
            return new \Nexus\Laravel\Identity\Adapters\IdentityOperationsAdapter(
                userPersist: $app[\Nexus\Identity\Contracts\UserPersistInterface::class],
                userQuery: $app[\Nexus\Identity\Contracts\UserQueryInterface::class],
                userAuthenticator: $app[\Nexus\Identity\Contracts\UserAuthenticatorInterface::class],
                identityTokenManager: $app[\Nexus\Identity\Contracts\TokenManagerInterface::class],
                sessionManager: $app[\Nexus\Identity\Contracts\SessionManagerInterface::class],
                mfaEnrollment: $app[\Nexus\Identity\Contracts\MfaEnrollmentServiceInterface::class],
                mfaVerification: $app[\Nexus\Identity\Contracts\MfaVerificationServiceInterface::class],
                notificationManager: $app[\Nexus\Notifier\Contracts\NotificationManagerInterface::class],
                auditLogRepository: $app[\Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface::class],
                cache: $app[\Nexus\Identity\Contracts\CacheRepositoryInterface::class],
                passwordHasher: $app[\Nexus\Identity\Contracts\PasswordHasherInterface::class],
                transactionManager: $app[\Nexus\IdentityOperations\Contracts\TransactionManagerInterface::class],
                logger: $app['log']
            );
        });

        $interfaces = [
            \Nexus\IdentityOperations\Services\UserCreatorInterface::class,
            \Nexus\IdentityOperations\Services\UserUpdaterInterface::class,
            \Nexus\IdentityOperations\Services\TenantUserAssignerInterface::class,
            \Nexus\IdentityOperations\Services\NotificationSenderInterface::class,
            \Nexus\IdentityOperations\Services\AuditLoggerInterface::class,
            \Nexus\IdentityOperations\Services\MfaEnrollerInterface::class,
            \Nexus\IdentityOperations\Services\MfaVerifierInterface::class,
            \Nexus\IdentityOperations\Services\MfaDisablerInterface::class,
            \Nexus\IdentityOperations\Services\BackupCodeGeneratorInterface::class,
            \Nexus\IdentityOperations\Services\AuthenticatorInterface::class,
            \Nexus\IdentityOperations\Services\TokenManagerInterface::class,
            \Nexus\IdentityOperations\Services\PasswordChangerInterface::class,
            \Nexus\IdentityOperations\Services\SessionValidatorInterface::class,
            \Nexus\IdentityOperations\Services\PermissionAssignerInterface::class,
            \Nexus\IdentityOperations\Services\PermissionRevokerInterface::class,
            \Nexus\IdentityOperations\Services\RoleAssignerInterface::class,
            \Nexus\IdentityOperations\Services\RoleRevokerInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $this->app->alias(\Nexus\Laravel\Identity\Adapters\IdentityOperationsAdapter::class, $interface);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/identity-adapter.php' => config_path('identity-adapter.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CacheRepositoryInterface::class,
            PermissionCheckerInterface::class,
            \Nexus\Laravel\Identity\Adapters\IdentityOperationsAdapter::class,
            \Nexus\IdentityOperations\Services\UserCreatorInterface::class,
            \Nexus\IdentityOperations\Services\UserUpdaterInterface::class,
            \Nexus\IdentityOperations\Services\TenantUserAssignerInterface::class,
            \Nexus\IdentityOperations\Services\NotificationSenderInterface::class,
            \Nexus\IdentityOperations\Services\AuditLoggerInterface::class,
            \Nexus\IdentityOperations\Services\MfaEnrollerInterface::class,
            \Nexus\IdentityOperations\Services\MfaVerifierInterface::class,
            \Nexus\IdentityOperations\Services\MfaDisablerInterface::class,
            \Nexus\IdentityOperations\Services\BackupCodeGeneratorInterface::class,
            \Nexus\IdentityOperations\Services\AuthenticatorInterface::class,
            \Nexus\IdentityOperations\Services\TokenManagerInterface::class,
            \Nexus\IdentityOperations\Services\PasswordChangerInterface::class,
            \Nexus\IdentityOperations\Services\SessionValidatorInterface::class,
            \Nexus\IdentityOperations\Services\PermissionAssignerInterface::class,
            \Nexus\IdentityOperations\Services\PermissionRevokerInterface::class,
            \Nexus\IdentityOperations\Services\RoleAssignerInterface::class,
            \Nexus\IdentityOperations\Services\RoleRevokerInterface::class,
        ];
    }
}
