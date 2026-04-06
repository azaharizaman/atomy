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
use App\Services\SourcingOperations\AtomyRfqInvitationPersist;
use App\Services\SourcingOperations\AtomyRfqInvitationQuery;
use App\Services\SourcingOperations\AtomyRfqInvitationReminder;
use App\Services\SourcingOperations\AtomyRfqLifecyclePersist;
use App\Services\SourcingOperations\AtomyRfqLifecycleQuery;
use App\Services\SourcingOperations\AtomyRfqLineItemPersist;
use App\Services\SourcingOperations\AtomyRfqLineItemQuery;
use App\Services\SourcingOperations\AtomySourcingRfqStatusTransitionPolicy;
use App\Services\SourcingOperations\AtomySourcingTransactionManager;
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
use Nexus\Sourcing\Contracts\RfqStatusTransitionPolicyInterface;
use Nexus\Sourcing\Services\RfqStatusTransitionPolicy;
use Nexus\SourcingOperations\Contracts\RfqInvitationPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqInvitationReminderPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecycleCoordinatorInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecyclePersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLifecycleQueryPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemPersistPortInterface;
use Nexus\SourcingOperations\Contracts\RfqLineItemQueryPortInterface;
use Nexus\SourcingOperations\Contracts\SourcingRfqStatusTransitionPolicyInterface;
use Nexus\SourcingOperations\Contracts\SourcingTransactionManagerInterface;
use Nexus\SourcingOperations\SourcingOperationsCoordinator;
use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Contracts\TaskQueryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionQueryInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineQueryInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLinePersistInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorDocumentRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use App\Adapters\QuotationIntelligence\OrchestratorDocumentRepository;
use App\Adapters\QuotationIntelligence\OrchestratorTenantRepository;
use App\Adapters\QuotationIntelligence\OrchestratorProcurementManager;
use App\Adapters\QuotationIntelligence\AtomyDecisionTrailWriter;
use App\Adapters\QuotationIntelligence\MockContentProcessor;
use App\Adapters\QuotationIntelligence\MockSemanticMapper;
use App\Adapters\QuotationIntelligence\Support\InMemoryUomRepository;
use App\Adapters\QuotationIntelligence\Support\StaticExchangeRateProvider;
use App\Adapters\QuoteIngestion\EloquentQuoteSubmissionQuery;
use App\Adapters\QuoteIngestion\EloquentQuoteSubmissionPersist;
use App\Adapters\QuoteIngestion\EloquentNormalizationSourceLineRepository;
use Nexus\QuotationIntelligence\Coordinators\QuotationIntelligenceCoordinator;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\CommercialTermsExtractorInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Services\QuoteNormalizationService;
use Nexus\QuotationIntelligence\Services\RegexCommercialTermsExtractor;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateResolverInterface;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\Services\ApprovalProcessCoordinator;
use Nexus\ApprovalOperations\Services\ApprovalTemplateResolver;
use App\Services\ApprovalOperations\AtomyApprovalPolicyRegistry;
use App\Services\ApprovalOperations\AtomyApprovalPolicyEngine;
use App\Services\ApprovalOperations\LaravelUlidGenerator;
use Nexus\Common\Contracts\UlidInterface;
use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Contracts\PolicyDefinitionDecoderInterface;
use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Contracts\PolicyValidatorInterface;
use Nexus\PolicyEngine\Services\PolicyEvaluator;
use Nexus\PolicyEngine\Services\JsonPolicyDecoder;
use Nexus\PolicyEngine\Services\PolicyValidator;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ReplayResponseFactoryInterface::class, IdempotencyReplayResponseFactory::class);

        // Nexus ApprovalOperations (operational approvals — distinct from RFQ quote flows).
        $this->app->singleton(AtomyApprovalPolicyRegistry::class);
        $this->app->singleton(PolicyValidatorInterface::class, PolicyValidator::class);
        $this->app->singleton(PolicyDefinitionDecoderInterface::class, static function ($app): PolicyDefinitionDecoderInterface {
            return new JsonPolicyDecoder($app->make(PolicyValidatorInterface::class));
        });
        $this->app->singleton(PolicyRegistryInterface::class, static function ($app): PolicyRegistryInterface {
            return $app->make(AtomyApprovalPolicyRegistry::class);
        });
        $this->app->singleton(PolicyEvaluator::class, static function ($app): PolicyEvaluator {
            return new PolicyEvaluator(
                $app->make(PolicyRegistryInterface::class),
                $app->make(PolicyValidatorInterface::class),
            );
        });
        $this->app->singleton(PolicyEngineInterface::class, static function ($app): PolicyEngineInterface {
            return new AtomyApprovalPolicyEngine(
                $app->make(PolicyEvaluator::class),
                $app->make(LoggerInterface::class),
            );
        });
        $this->app->singleton(UlidInterface::class, LaravelUlidGenerator::class);
        $this->app->singleton(ApprovalTemplateResolver::class);
        $this->app->bind(ApprovalTemplateResolverInterface::class, static function ($app): ApprovalTemplateResolverInterface {
            return $app->make(ApprovalTemplateResolver::class);
        });
        $this->app->singleton(ApprovalProcessCoordinator::class, static function ($app): ApprovalProcessCoordinator {
            return new ApprovalProcessCoordinator(
                $app->make(ApprovalTemplateResolverInterface::class),
                $app->make(ApprovalInstancePersistInterface::class),
                $app->make(ApprovalInstanceQueryInterface::class),
                $app->make(PolicyEngineInterface::class),
                $app->make(OperationalWorkflowBridgeInterface::class),
                $app->make(UlidInterface::class),
                $app->make(ApprovalCommentPersistInterface::class),
            );
        });

        // Nexus QuotationIntelligence: Intelligent quote ingestion pipeline.
        $this->app->bind(UomRepositoryInterface::class, InMemoryUomRepository::class);
        $this->app->singleton(ExchangeRateProviderInterface::class, StaticExchangeRateProvider::class);
        $this->app->singleton(OrchestratorDocumentRepositoryInterface::class, OrchestratorDocumentRepository::class);
        $this->app->singleton(OrchestratorTenantRepositoryInterface::class, OrchestratorTenantRepository::class);
        $this->app->singleton(OrchestratorProcurementManagerInterface::class, OrchestratorProcurementManager::class);
        $this->app->singleton(DecisionTrailWriterInterface::class, AtomyDecisionTrailWriter::class);
        $this->app->singleton(OrchestratorContentProcessorInterface::class, MockContentProcessor::class);
        $this->app->singleton(SemanticMapperInterface::class, MockSemanticMapper::class);
        $this->app->singleton(QuoteNormalizationServiceInterface::class, QuoteNormalizationService::class);
        $this->app->singleton(CommercialTermsExtractorInterface::class, RegexCommercialTermsExtractor::class);
        $this->app->singleton(RiskAssessmentServiceInterface::class, RuleBasedRiskAssessmentService::class);
        $this->app->singleton(QuotationIntelligenceCoordinatorInterface::class, static function ($app): QuotationIntelligenceCoordinator {
            return new QuotationIntelligenceCoordinator(
                $app->make(OrchestratorContentProcessorInterface::class),
                $app->make(OrchestratorDocumentRepositoryInterface::class),
                $app->make(OrchestratorTenantRepositoryInterface::class),
                $app->make(OrchestratorProcurementManagerInterface::class),
                $app->make(SemanticMapperInterface::class),
                $app->make(QuoteNormalizationServiceInterface::class),
                $app->make(CommercialTermsExtractorInterface::class),
                $app->make(RiskAssessmentServiceInterface::class),
                $app->make(LoggerInterface::class),
            );
        });

        // Nexus QuoteIngestion: Orchestrator for quote submission processing.
        $this->app->singleton(QuoteSubmissionQueryInterface::class, EloquentQuoteSubmissionQuery::class);
        $this->app->singleton(QuoteSubmissionPersistInterface::class, EloquentQuoteSubmissionPersist::class);
        $this->app->singleton(NormalizationSourceLineQueryInterface::class, EloquentNormalizationSourceLineRepository::class);
        $this->app->singleton(NormalizationSourceLinePersistInterface::class, EloquentNormalizationSourceLineRepository::class);
        $this->app->singleton(QuoteIngestionOrchestrator::class, static function ($app): QuoteIngestionOrchestrator {
            return new QuoteIngestionOrchestrator(
                $app->make(QuotationIntelligenceCoordinatorInterface::class),
                $app->make(DecisionTrailWriterInterface::class),
                $app->make(TenantContextInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(QuoteSubmissionQueryInterface::class),
                $app->make(QuoteSubmissionPersistInterface::class),
                $app->make(NormalizationSourceLineQueryInterface::class),
                $app->make(NormalizationSourceLinePersistInterface::class),
            );
        });

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

        // Nexus SourcingOperations: RFQ lifecycle orchestration + Laravel adapters.
        $this->app->bind(RfqLifecycleQueryPortInterface::class, AtomyRfqLifecycleQuery::class);
        $this->app->bind(RfqLifecyclePersistPortInterface::class, AtomyRfqLifecyclePersist::class);
        $this->app->bind(RfqLineItemQueryPortInterface::class, AtomyRfqLineItemQuery::class);
        $this->app->bind(RfqLineItemPersistPortInterface::class, AtomyRfqLineItemPersist::class);
        $this->app->bind(RfqInvitationQueryPortInterface::class, AtomyRfqInvitationQuery::class);
        $this->app->bind(RfqInvitationPersistPortInterface::class, AtomyRfqInvitationPersist::class);
        $this->app->bind(RfqInvitationReminderPortInterface::class, AtomyRfqInvitationReminder::class);
        $this->app->singleton(RfqStatusTransitionPolicyInterface::class, RfqStatusTransitionPolicy::class);
        $this->app->bind(SourcingRfqStatusTransitionPolicyInterface::class, AtomySourcingRfqStatusTransitionPolicy::class);
        $this->app->bind(SourcingTransactionManagerInterface::class, AtomySourcingTransactionManager::class);
        $this->app->singleton(RfqLifecycleCoordinatorInterface::class, SourcingOperationsCoordinator::class);

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
