<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Queue;
use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\SsoProviderInterface;
use Nexus\Laravel\Identity\Adapters\CacheRepositoryAdapter;
use Nexus\Laravel\Identity\Adapters\PermissionCheckerAdapter;
use Nexus\Laravel\Identity\Adapters\UserPersistAdapter;
use Nexus\Laravel\Identity\Sso\OidcSsoProviderAdapter;
use Nexus\IdentityOperations\Services\SsoLoginServiceInterface;
use Nexus\IdentityOperations\Services\OidcSsoLoginService;
use Nexus\IdentityOperations\Services\SsoStateStoreInterface;
use Nexus\Laravel\Identity\Sso\LaravelSsoStateStore;
use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Nexus\IdentityOperations\Contracts\UserAuthenticationServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\Coordinators\UserAuthenticationCoordinator;
use Nexus\IdentityOperations\Services\UserAuthenticationService;
use Nexus\IdentityOperations\DataProviders\UserContextDataProvider;
use Nexus\IdentityOperations\DataProviders\UserQueryInterface as OrchestratorUserQueryInterface;
use Nexus\IdentityOperations\DataProviders\PermissionQueryInterface as OrchestratorPermissionQueryInterface;
use Nexus\Laravel\Identity\DataProviders\LaravelUserQuery;
use Nexus\Laravel\Identity\DataProviders\LaravelPermissionQuery;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Identity\Contracts\BackupCodeRepositoryInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Laravel\Identity\Repositories\EloquentUserRepository;
use Nexus\Laravel\Identity\Repositories\EloquentRoleRepository;
use Nexus\Laravel\Identity\Repositories\EloquentPermissionRepository;
use Nexus\Laravel\Identity\Repositories\EloquentMfaEnrollmentRepository;
use Nexus\Laravel\Identity\Repositories\EloquentBackupCodeRepository;
use Nexus\Laravel\Identity\Adapters\DatabaseSessionManager;

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
        // Register repositories
        $this->app->singleton(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->singleton(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->singleton(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);
        $this->app->singleton(MfaEnrollmentRepositoryInterface::class, EloquentMfaEnrollmentRepository::class);
        $this->app->singleton(BackupCodeRepositoryInterface::class, EloquentBackupCodeRepository::class);
        $this->app->singleton(SessionManagerInterface::class, DatabaseSessionManager::class);

        // Register UserPersist adapter
        $this->app->singleton(\Nexus\Identity\Contracts\UserPersistInterface::class, function () {
            return new UserPersistAdapter();
        });

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
                permissionRepository: $app[PermissionRepositoryInterface::class],
                roleRepository: $app[RoleRepositoryInterface::class],
                userRepository: $app[UserRepositoryInterface::class],
                logger: $app['log']
            );
        });

        // Register transaction manager
        $this->app->singleton(\Nexus\IdentityOperations\Contracts\TransactionManagerInterface::class, function ($app) {
            return new \Nexus\Laravel\Identity\Adapters\LaravelTransactionManager();
        });

        // Register IdentityOperations orchestrator adapter
        $this->registerIdentityOperationsAdapters();

        // Register OIDC SSO provider (Identity contract) + orchestrator SSO service
        $this->app->singleton(SsoProviderInterface::class, function ($app): SsoProviderInterface {
            return new OidcSsoProviderAdapter(
                userQuery: $app[\Nexus\Identity\Contracts\UserQueryInterface::class],
                userPersist: $app[\Nexus\Identity\Contracts\UserPersistInterface::class],
                passwordHasher: $app[\Nexus\Identity\Contracts\PasswordHasherInterface::class],
                config: $app['config'],
            );
        });

        $this->app->singleton(SsoStateStoreInterface::class, function ($app): SsoStateStoreInterface {
            return new LaravelSsoStateStore($app['cache.store']);
        });

        $this->app->singleton(SsoLoginServiceInterface::class, function ($app): SsoLoginServiceInterface {
            return new OidcSsoLoginService(
                provider: $app[SsoProviderInterface::class],
                stateStore: $app[SsoStateStoreInterface::class],
                logger: $app['log'],
            );
        });

        // UserAuthenticationCoordinator wiring (plan requirement: route SSO through coordinator)
        $this->app->singleton(OrchestratorUserQueryInterface::class, function ($app): OrchestratorUserQueryInterface {
            return new LaravelUserQuery($app[\Nexus\Identity\Contracts\UserQueryInterface::class]);
        });
        $this->app->singleton(OrchestratorPermissionQueryInterface::class, function ($app): OrchestratorPermissionQueryInterface {
            return new LaravelPermissionQuery(
                $app[\Nexus\Identity\Contracts\PermissionQueryInterface::class],
                $app[\Nexus\Identity\Contracts\RoleQueryInterface::class],
                $app[\Nexus\Identity\Contracts\UserQueryInterface::class],
            );
        });
        $this->app->singleton(UserContextProviderInterface::class, function ($app): UserContextProviderInterface {
            return new UserContextDataProvider(
                userQuery: $app[OrchestratorUserQueryInterface::class],
                permissionQuery: $app[OrchestratorPermissionQueryInterface::class],
            );
        });
        $this->app->singleton(UserAuthenticationServiceInterface::class, function ($app): UserAuthenticationServiceInterface {
            return new UserAuthenticationService(
                authenticator: $app[\Nexus\IdentityOperations\Services\AuthenticatorInterface::class],
                tokenManager: $app[\Nexus\IdentityOperations\Services\TokenManagerInterface::class],
                passwordChanger: $app[\Nexus\IdentityOperations\Services\PasswordChangerInterface::class],
                sessionValidator: $app[\Nexus\IdentityOperations\Services\SessionValidatorInterface::class],
                auditLogger: $app[\Nexus\IdentityOperations\Services\AuditLoggerInterface::class],
                logger: $app['log'],
            );
        });
        $this->app->singleton(UserAuthenticationCoordinatorInterface::class, function ($app): UserAuthenticationCoordinatorInterface {
            return new UserAuthenticationCoordinator(
                authService: $app[UserAuthenticationServiceInterface::class],
                contextDataProvider: $app[UserContextProviderInterface::class],
                ssoLogin: $app[SsoLoginServiceInterface::class],
                logger: $app['log'],
            );
        });
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
                queue: $app[Queue::class],
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
            \Nexus\Identity\Contracts\UserPersistInterface::class,
            UserRepositoryInterface::class,
            RoleRepositoryInterface::class,
            PermissionRepositoryInterface::class,
            MfaEnrollmentRepositoryInterface::class,
            BackupCodeRepositoryInterface::class,
            SessionManagerInterface::class,
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
