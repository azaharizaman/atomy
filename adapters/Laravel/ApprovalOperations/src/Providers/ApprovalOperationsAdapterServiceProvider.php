<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplatePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateResolverInterface;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\Services\ApprovalTemplateResolver;
use Nexus\Laravel\ApprovalOperations\Bridge\GeneratingOperationalWorkflowBridge;
use Nexus\Laravel\ApprovalOperations\Persistence\EloquentApprovalCommentPersist;
use Nexus\Laravel\ApprovalOperations\Persistence\EloquentApprovalInstancePersist;
use Nexus\Laravel\ApprovalOperations\Persistence\EloquentApprovalInstanceQuery;
use Nexus\Laravel\ApprovalOperations\Persistence\EloquentApprovalTemplatePersist;
use Nexus\Laravel\ApprovalOperations\Persistence\EloquentApprovalTemplateQuery;

final class ApprovalOperationsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApprovalTemplateResolver::class);
        $this->app->bind(ApprovalTemplateResolverInterface::class, static function ($app): ApprovalTemplateResolverInterface {
            return $app->make(ApprovalTemplateResolver::class);
        });
        $this->app->bind(ApprovalTemplateQueryInterface::class, EloquentApprovalTemplateQuery::class);
        $this->app->bind(ApprovalTemplatePersistInterface::class, EloquentApprovalTemplatePersist::class);
        $this->app->bind(ApprovalInstanceQueryInterface::class, EloquentApprovalInstanceQuery::class);
        $this->app->bind(ApprovalInstancePersistInterface::class, EloquentApprovalInstancePersist::class);
        $this->app->bind(ApprovalCommentPersistInterface::class, EloquentApprovalCommentPersist::class);
        $this->app->bind(OperationalWorkflowBridgeInterface::class, GeneratingOperationalWorkflowBridge::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
