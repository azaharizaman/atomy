<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Document;
use App\Repositories\DbDocumentRelationshipRepository;
use App\Repositories\DbDocumentRepository;
use App\Repositories\DbDocumentVersionRepository;
use App\Services\DefaultRetentionPolicy;
use App\Services\DocumentPermissionChecker;
use App\Services\NullContentProcessor;
use Illuminate\Support\ServiceProvider;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentSearchInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Services\DocumentSearchService;
use Nexus\Document\Services\RelationshipManager;
use Nexus\Document\Services\RetentionService;
use Nexus\Document\Services\VersionManager;

/**
 * Document service provider.
 *
 * Binds all Document package contracts to Atomy implementations.
 */
final class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            DocumentRepositoryInterface::class,
            DbDocumentRepository::class
        );

        $this->app->singleton(
            DocumentVersionRepositoryInterface::class,
            DbDocumentVersionRepository::class
        );

        $this->app->singleton(
            DocumentRelationshipRepositoryInterface::class,
            DbDocumentRelationshipRepository::class
        );

        // Bind services
        $this->app->singleton(
            PermissionCheckerInterface::class,
            DocumentPermissionChecker::class
        );

        $this->app->singleton(
            ContentProcessorInterface::class,
            NullContentProcessor::class
        );

        $this->app->singleton(
            RetentionPolicyInterface::class,
            DefaultRetentionPolicy::class
        );

        // Bind package managers
        $this->app->singleton(DocumentManager::class);
        $this->app->singleton(VersionManager::class);
        $this->app->singleton(RelationshipManager::class);
        $this->app->singleton(RetentionService::class);

        // Bind search service
        $this->app->singleton(DocumentSearchInterface::class, DocumentSearchService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Apply global scopes
        Document::addGlobalScope('tenant', function ($query) {
            // TODO: Apply tenant scope when TenantContextManager is available
            // $tenantId = app(TenantContextManager::class)->getCurrentTenantId();
            // $query->where('tenant_id', $tenantId);
        });
    }
}
