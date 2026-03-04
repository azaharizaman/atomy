<?php

declare(strict_types=1);

namespace Nexus\Document;

use Illuminate\Support\ServiceProvider;
use Nexus\Document\Contracts\AsyncBatchUploadInterface;
use Nexus\Document\Contracts\AuditLogManagerInterface;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\HasherInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Contracts\StorageDriverInterface;
use Nexus\Document\Contracts\TenantContextInterface;
use Nexus\Document\Services\DocumentAuditLogAdapter;
use Nexus\Document\Services\DocumentPermissionCheckerAdapter;
use Nexus\Document\Services\DocumentTenantContextAdapter;
use Nexus\Document\Services\LocalStorageDriver;
use Nexus\Document\Services\NoOpAsyncBatchUpload;
use Nexus\Document\Services\NoOpContentProcessor;
use Nexus\Document\Services\Sha256Hasher;

use Nexus\Identity\Contracts\PermissionCheckerInterface as IdentityPermissionChecker;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface as GlobalTenantContext;
use Nexus\AuditLogger\Services\AuditLogManager;

/**
 * Service provider for the Document package.
 */
final class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogManagerInterface::class, function ($app) {
            return new DocumentAuditLogAdapter($app->make(AuditLogManager::class));
        });

        $this->app->singleton(ContentProcessorInterface::class, NoOpContentProcessor::class);

        $this->app->singleton(StorageDriverInterface::class, function ($app) {
            $basePath = config('document.storage_path') ?? env('DOCUMENT_STORAGE_PATH', '/tmp/atomy/storage');
            return new LocalStorageDriver($basePath);
        });

        $this->app->singleton(HasherInterface::class, Sha256Hasher::class);

        $this->app->singleton(PermissionCheckerInterface::class, function ($app) {
            return new DocumentPermissionCheckerAdapter(
                $app->make(IdentityPermissionChecker::class),
                $app->make(UserRepositoryInterface::class)
            );
        });

        $this->app->singleton(TenantContextInterface::class, function ($app) {
            return new DocumentTenantContextAdapter($app->make(GlobalTenantContext::class));
        });

        $this->app->singleton(AsyncBatchUploadInterface::class, NoOpAsyncBatchUpload::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
