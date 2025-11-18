<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\DbPermissionRepository;
use App\Repositories\DbRoleRepository;
use App\Repositories\DbUserRepository;
use App\Repositories\DbNotificationHistoryRepository;
use App\Repositories\DbNotificationPreferenceRepository;
use App\Repositories\DbNotificationQueue;
use App\Repositories\DbNotificationTemplateRepository;
use App\Services\Channels\EmailChannel;
use App\Services\Channels\InAppChannel;
use App\Services\Channels\PushChannel;
use App\Services\Channels\SmsChannel;
use App\Services\LaravelPasswordHasher;
use App\Services\LaravelPasswordValidator;
use App\Services\LaravelSessionManager;
use App\Services\LaravelTokenManager;
use App\Services\LaravelUserAuthenticator;
use App\Services\NotificationRenderer;
use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\PermissionManagerInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Contracts\RoleManagerInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserManagerInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Services\AuthenticationService;
use Nexus\Identity\Services\PermissionChecker;
use Nexus\Identity\Services\PermissionManager;
use Nexus\Identity\Services\RoleManager;
use Nexus\Identity\Services\UserManager;
use Nexus\Notifier\Contracts\EmailChannelInterface;
use Nexus\Notifier\Contracts\InAppChannelInterface;
use Nexus\Notifier\Contracts\NotificationHistoryRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\Contracts\NotificationPreferenceRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationQueueInterface;
use Nexus\Notifier\Contracts\NotificationRendererInterface;
use Nexus\Notifier\Contracts\NotificationTemplateRepositoryInterface;
use Nexus\Notifier\Contracts\PushChannelInterface;
use Nexus\Notifier\Contracts\SmsChannelInterface;
use Nexus\Notifier\Services\NotificationManager;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
