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
        $this->app->singleton(AuditLogManagerInterface::class, DocumentAuditLogAdapter::class);
        $this->app->singleton(ContentProcessorInterface::class, NoOpContentProcessor::class);
        $this->app->singleton(StorageDriverInterface::class, LocalStorageDriver::class);
        $this->app->singleton(HasherInterface::class, Sha256Hasher::class);
        $this->app->singleton(PermissionCheckerInterface::class, DocumentPermissionCheckerAdapter::class);
        $this->app->singleton(TenantContextInterface::class, DocumentTenantContextAdapter::class);
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
