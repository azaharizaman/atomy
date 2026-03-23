<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Idempotency\IdempotencyReplayResponseFactory;
use App\Contracts\JwtServiceInterface;
use App\Contracts\PasswordResetServiceInterface;
use App\Services\Identity\AtomyIdentityTokenManagerStub;
use App\Services\Identity\AtomyNoopAuditLogRepository;
use App\Services\Identity\AtomyNoopMfaEnrollmentService;
use App\Services\Identity\AtomyNoopMfaVerificationService;
use App\Services\Identity\AtomyPasswordHasher;
use App\Services\Identity\AtomyPermissionQueryStub;
use App\Services\Identity\AtomyRoleQueryStub;
use App\Services\Identity\AtomySessionManagerStub;
use App\Services\Identity\AtomyUserAuthenticator;
use App\Services\Identity\AtomyUserPersist;
use App\Services\Identity\AtomyUserQuery;
use App\Services\Auth\PasswordResetService;
use App\Services\JwtService;
use App\Services\Project\AtomyIncompleteTaskCount;
use App\Services\Project\AtomyProjectPersist;
use App\Services\Project\AtomyProjectQuery;
use App\Services\ProjectManagementOperations\AtomyExpenseHealthService;
use App\Services\ProjectManagementOperations\AtomyLaborHealthService;
use App\Services\ProjectManagementOperations\AtomyMilestoneBillingService;
use App\Services\ProjectManagementOperations\AtomyProjectTaskIdsQuery;
use App\Services\ProjectManagementOperations\AtomyTimelineDriftService;
use App\Services\Task\AtomyTaskPersist;
use App\Services\Task\AtomyTaskQuery;
use App\Services\Tenant\RequestTenantContext;
use App\OpenApi\IdempotencyErrorCodesDocumentTransformer;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\Identity\Contracts\MfaEnrollmentServiceInterface;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\PermissionQueryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface as IdentityTokenManagerInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface as IdentityUserQueryInterface;
use Nexus\Project\Contracts\IncompleteTaskCountInterface;
use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Nexus\Project\Services\ProjectManager;
use Nexus\Task\Contracts\DependencyGraphInterface;
use Nexus\Task\Services\DependencyGraphService;
use Nexus\Task\Services\TaskManager;
use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;
use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Contracts\TaskQueryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\Services\VertexAIMockProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ReplayResponseFactoryInterface::class, IdempotencyReplayResponseFactory::class);

        // Nexus MachineLearning: Quote extraction mock for Alpha testing.
        $this->app->singleton(QuoteExtractionServiceInterface::class, VertexAIMockProvider::class);

        $this->app->singleton(JwtServiceInterface::class, function (): JwtServiceInterface {
            $secret = (string) config('jwt.secret');

            if ($secret === '') {
                throw new \InvalidArgumentException('JWT_SECRET is not set or empty in environment configuration.');
            }

            return new JwtService(
                $secret,
                (int) config('jwt.ttl'),
                (int) config('jwt.refresh_ttl'),
                (string) config('jwt.algo'),
                (string) config('jwt.issuer'),
            );
        });

        $this->app->singleton(PasswordResetService::class, function (): PasswordResetService {
            $ttl = (int) config('auth.passwords.users.expire', 60);
            $ttl = max(1, $ttl);

            return new PasswordResetService($ttl);
        });

        $this->app->bind(PasswordResetServiceInterface::class, static fn ($app): PasswordResetServiceInterface => $app->make(PasswordResetService::class));

        // Nexus Project: Laravel implementations + package Manager.
        $this->app->bind(ProjectPersistInterface::class, AtomyProjectPersist::class);
        $this->app->bind(ProjectQueryInterface::class, AtomyProjectQuery::class);
        $this->app->bind(IncompleteTaskCountInterface::class, AtomyIncompleteTaskCount::class);
        $this->app->bind(ProjectManagerInterface::class, ProjectManager::class);

        // Nexus Task: Laravel implementations + package Manager and DependencyGraph.
        $this->app->bind(TaskPersistInterface::class, AtomyTaskPersist::class);
        $this->app->bind(TaskQueryInterface::class, AtomyTaskQuery::class);
        $this->app->bind(DependencyGraphInterface::class, DependencyGraphService::class);
        $this->app->bind(TaskManagerInterface::class, TaskManager::class);

        // Tenant context for tenant-scoped persistence/query services.
        $this->app->bind(TenantContextInterface::class, RequestTenantContext::class);

        // ProjectManagementOperations orchestrator and health services.
        $this->app->bind(LaborHealthServiceInterface::class, AtomyLaborHealthService::class);
        $this->app->bind(ExpenseHealthServiceInterface::class, AtomyExpenseHealthService::class);
        $this->app->bind(TimelineDriftServiceInterface::class, AtomyTimelineDriftService::class);
        $this->app->bind(MilestoneBillingServiceInterface::class, AtomyMilestoneBillingService::class);
        $this->app->bind(ProjectTaskIdsQueryInterface::class, AtomyProjectTaskIdsQuery::class);
        $this->app->singleton(ProjectManagementOperationsCoordinator::class);

        // Nexus Identity (L3): required by nexus/laravel-identity-adapter for SSO + coordinator resolution.
        $this->app->singleton(IdentityUserQueryInterface::class, AtomyUserQuery::class);
        $this->app->singleton(UserPersistInterface::class, AtomyUserPersist::class);
        $this->app->singleton(PasswordHasherInterface::class, AtomyPasswordHasher::class);
        $this->app->singleton(UserAuthenticatorInterface::class, AtomyUserAuthenticator::class);
        $this->app->singleton(IdentityTokenManagerInterface::class, AtomyIdentityTokenManagerStub::class);
        $this->app->singleton(SessionManagerInterface::class, AtomySessionManagerStub::class);
        $this->app->singleton(MfaEnrollmentServiceInterface::class, AtomyNoopMfaEnrollmentService::class);
        $this->app->singleton(MfaVerificationServiceInterface::class, AtomyNoopMfaVerificationService::class);
        $this->app->singleton(PermissionQueryInterface::class, AtomyPermissionQueryStub::class);
        $this->app->singleton(RoleQueryInterface::class, AtomyRoleQueryStub::class);
        $this->app->singleton(AuditLogRepositoryInterface::class, AtomyNoopAuditLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()->withDocumentTransformers([
            IdempotencyErrorCodesDocumentTransformer::class,
        ]);
    }
}
