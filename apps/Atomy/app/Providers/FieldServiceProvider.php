<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbChecklistRepository;
use App\Repositories\DbPartsConsumptionRepository;
use App\Repositories\DbServiceContractRepository;
use App\Repositories\DbSignatureRepository;
use App\Repositories\DbWorkOrderRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\FieldService\Contracts\ChecklistRepositoryInterface;
use Nexus\FieldService\Contracts\GpsTrackerInterface;
use Nexus\FieldService\Contracts\MaintenanceDeduplicationInterface;
use Nexus\FieldService\Contracts\MobileSyncManagerInterface;
use Nexus\FieldService\Contracts\PartsConsumptionRepositoryInterface;
use Nexus\FieldService\Contracts\RouteOptimizerInterface;
use Nexus\FieldService\Contracts\ServiceContractRepositoryInterface;
use Nexus\FieldService\Contracts\SignatureRepositoryInterface;
use Nexus\FieldService\Contracts\SlaCalculatorInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Core\Assignment\DefaultAssignmentStrategy;
use Nexus\FieldService\Core\Routing\DefaultRouteOptimizer;
use Nexus\FieldService\Core\Sync\LastWriteWinsSyncManager;
use Nexus\FieldService\Core\Maintenance\MaintenanceDeduplicationService;
use Nexus\FieldService\Services\PartsConsumptionManager;
use Nexus\FieldService\Services\ServiceReportGenerator;
use Nexus\FieldService\Services\TechnicianDispatcher;
use Nexus\FieldService\Services\WorkOrderManager;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Illuminate\Support\Facades\Event;
use Nexus\FieldService\Events\WorkOrderCompletedEvent;
use Nexus\FieldService\Events\PartsConsumedEvent;
use Nexus\FieldService\Events\SlaBreachedEvent;
use Nexus\FieldService\Events\WorkOrderVerifiedEvent;
use App\Listeners\FieldService\PostRevenueOnWorkOrderCompletion;
use App\Listeners\FieldService\DeductInventoryOnPartsConsumed;
use App\Listeners\FieldService\EscalateOnSlaBreach;
use App\Listeners\FieldService\GenerateReportOnVerification;

final class FieldServiceProvider extends ServiceProvider
{
    /**
     * Register Field Service bindings
     */
    public function register(): void
    {
        // Repository bindings with tenant context
        $this->app->bind(WorkOrderRepositoryInterface::class, function ($app) {
            $tenantContext = $app->make(TenantContextInterface::class);
            return new DbWorkOrderRepository($tenantContext->getCurrentTenantId());
        });

        $this->app->bind(ServiceContractRepositoryInterface::class, function ($app) {
            $tenantContext = $app->make(TenantContextInterface::class);
            return new DbServiceContractRepository($tenantContext->getCurrentTenantId());
        });

        $this->app->bind(ChecklistRepositoryInterface::class, function ($app) {
            $tenantContext = $app->make(TenantContextInterface::class);
            return new DbChecklistRepository($tenantContext->getCurrentTenantId());
        });

        $this->app->singleton(PartsConsumptionRepositoryInterface::class, DbPartsConsumptionRepository::class);
        $this->app->singleton(SignatureRepositoryInterface::class, DbSignatureRepository::class);

        // Core Engine bindings (Tier 1 defaults)
        $this->app->singleton(TechnicianAssignmentStrategyInterface::class, DefaultAssignmentStrategy::class);
        $this->app->singleton(RouteOptimizerInterface::class, DefaultRouteOptimizer::class);
        $this->app->singleton(MobileSyncManagerInterface::class, LastWriteWinsSyncManager::class);
        $this->app->singleton(MaintenanceDeduplicationInterface::class, MaintenanceDeduplicationService::class);

        // SLA Calculator binding (to be implemented)
        $this->app->singleton(SlaCalculatorInterface::class, function ($app) {
            return new class implements SlaCalculatorInterface {
                public function calculateDeadline(
                    \DateTimeImmutable $createdAt,
                    string $responseTime,
                    string $priority
                ): \DateTimeImmutable {
                    // Simple implementation: parse response time like "4H", "24H", "48H"
                    $hours = (int) filter_var($responseTime, FILTER_SANITIZE_NUMBER_INT);
                    return $createdAt->modify("+{$hours} hours");
                }

                public function isBreached(\DateTimeImmutable $deadline, \DateTimeImmutable $completedAt): bool
                {
                    return $completedAt > $deadline;
                }

                public function getBreachDuration(\DateTimeImmutable $deadline, \DateTimeImmutable $completedAt): \DateInterval
                {
                    if ($completedAt <= $deadline) {
                        return new \DateInterval('PT0S');
                    }
                    return $deadline->diff($completedAt);
                }
            };
        });

        // GPS Tracker binding (stub for Tier 1)
        $this->app->singleton(GpsTrackerInterface::class, function ($app) {
            return new class implements GpsTrackerInterface {
                public function captureLocation(string $workOrderId, string $technicianId): array
                {
                    // Stub: Returns null coordinates (requires mobile app integration)
                    return ['latitude' => null, 'longitude' => null, 'accuracy' => null];
                }

                public function getLocationHistory(string $workOrderId): array
                {
                    return [];
                }
            };
        });

        // Business Service bindings (these auto-resolve via constructor injection)
        // No explicit binding needed as Laravel resolves them automatically
        // But we can register them as singletons if desired for performance
        $this->app->singleton(WorkOrderManager::class);
        $this->app->singleton(TechnicianDispatcher::class);
        $this->app->singleton(PartsConsumptionManager::class);
        $this->app->singleton(ServiceReportGenerator::class);
    }

    /**
     * Bootstrap Field Service services
     */
    public function boot(): void
    {
        // Register event listeners for domain event integrations
        Event::listen(WorkOrderCompletedEvent::class, PostRevenueOnWorkOrderCompletion::class);
        Event::listen(PartsConsumedEvent::class, DeductInventoryOnPartsConsumed::class);
        Event::listen(SlaBreachedEvent::class, EscalateOnSlaBreach::class);
        Event::listen(WorkOrderVerifiedEvent::class, GenerateReportOnVerification::class);
    }
}
