<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\QuoteComparisonRequestDto;
use App\Entity\QuoteApprovalDecision;
use App\Entity\QuoteComparisonRun;
use App\Entity\QuoteDecisionTrailEntry;
use App\Repository\QuoteComparisonRunRepository;
use App\Repository\QuoteDecisionTrailEntryRepository;
use App\Service\QuoteApprovalApplicationService;
use App\Service\QuoteComparisonApplicationService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Nexus\QuotationIntelligence\Services\HashChainedDecisionTrailWriter;
use Nexus\QuotationIntelligence\Services\HighRiskApprovalGateService;
use Nexus\QuotationIntelligence\Services\QuoteComparisonMatrixService;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Nexus\QuotationIntelligence\Services\WeightedVendorScoringService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class QuoteWorkflowIntegrationTest extends TestCase
{
    private ?EntityManager $entityManager = null;
    private QuoteComparisonApplicationService $comparisonService;
    private QuoteApprovalApplicationService $approvalService;
    private QuoteComparisonRunRepository $runRepository;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for integration test execution.');
        }

        if (!Type::hasType(UlidType::NAME)) {
            Type::addType(UlidType::NAME, UlidType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([
            __DIR__ . '/../../src/Entity',
        ], true);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [
            $this->entityManager->getClassMetadata(QuoteComparisonRun::class),
            $this->entityManager->getClassMetadata(QuoteDecisionTrailEntry::class),
            $this->entityManager->getClassMetadata(QuoteApprovalDecision::class),
        ];
        $schemaTool->createSchema($metadata);

        $registry = new class($this->entityManager) implements ManagerRegistry {
            public function __construct(private readonly EntityManager $entityManager)
            {
            }

            public function getDefaultConnectionName(): string
            {
                return 'default';
            }

            public function getConnection(?string $name = null): object
            {
                return $this->entityManager->getConnection();
            }

            public function getConnections(): array
            {
                return ['default' => $this->entityManager->getConnection()];
            }

            public function getConnectionNames(): array
            {
                return ['default' => 'default'];
            }

            public function getDefaultManagerName(): string
            {
                return 'default';
            }

            public function getManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getManagers(): array
            {
                return ['default' => $this->entityManager];
            }

            public function resetManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getManagerNames(): array
            {
                return ['default' => 'default'];
            }

            public function getRepository(string $persistentObject, ?string $persistentManagerName = null): ObjectRepository
            {
                return $this->entityManager->getRepository($persistentObject);
            }

            public function getManagerForClass(string $class): ?ObjectManager
            {
                return $this->entityManager;
            }
        };

        $this->runRepository = new QuoteComparisonRunRepository($registry);
        $trailRepository = new QuoteDecisionTrailEntryRepository($registry);

        $this->comparisonService = new QuoteComparisonApplicationService(
            runRepository: $this->runRepository,
            entityManager: $this->entityManager,
            matrixService: new QuoteComparisonMatrixService(),
            riskService: new RuleBasedRiskAssessmentService(new NullLogger()),
            scoringService: new WeightedVendorScoringService(),
            approvalGateService: new HighRiskApprovalGateService(),
            decisionTrailWriter: new HashChainedDecisionTrailWriter()
        );

        $this->approvalService = new QuoteApprovalApplicationService(
            runRepository: $this->runRepository,
            trailRepository: $trailRepository,
            entityManager: $this->entityManager
        );
    }

    public function testCompareSupportsIdempotentReplay(): void
    {
        $request = QuoteComparisonRequestDto::fromPayload([
            'rfq_id' => 'RFQ-100',
            'vendors' => [
                [
                    'vendor_id' => 'vendor-a',
                    'lines' => [
                        [
                            'rfq_line_id' => 'line-1',
                            'vendor_description' => 'Steel sheet',
                            'taxonomy_code' => 'METAL',
                            'quoted_quantity' => 10,
                            'quoted_unit' => 'pcs',
                            'normalized_quantity' => 10,
                            'quoted_unit_price' => 100,
                            'normalized_unit_price' => 100,
                            'ai_confidence' => 0.95,
                        ],
                    ],
                ],
            ],
        ], 'idem-100');

        $first = $this->comparisonService->compare('tenant-1', $request);
        $second = $this->comparisonService->compare('tenant-1', $request);

        self::assertArrayHasKey('run_id', $first);
        self::assertFalse($first['idempotent_replay']);
        self::assertTrue($second['idempotent_replay']);
        self::assertSame($first['run_id'], $second['run_id']);
    }

    public function testApprovalDecisionUpdatesRunStatus(): void
    {
        $request = QuoteComparisonRequestDto::fromPayload([
            'rfq_id' => 'RFQ-200',
            'vendors' => [
                [
                    'vendor_id' => 'vendor-risky',
                    'risks' => [
                        ['level' => 'high', 'message' => 'Compliance warning'],
                    ],
                    'lines' => [
                        [
                            'rfq_line_id' => 'line-1',
                            'vendor_description' => 'Service package',
                            'taxonomy_code' => 'SERV',
                            'quoted_quantity' => 1,
                            'quoted_unit' => 'lot',
                            'normalized_quantity' => 1,
                            'quoted_unit_price' => 1000,
                            'normalized_unit_price' => 1000,
                            'ai_confidence' => 0.9,
                        ],
                    ],
                ],
            ],
        ], null);

        $result = $this->comparisonService->compare('tenant-2', $request);
        self::assertSame('pending_approval', $result['status']);

        $approval = $this->approvalService->decide(
            'tenant-2',
            (string)$result['run_id'],
            'approve',
            'Budget owner approved after review',
            'owner@example.com'
        );

        self::assertSame('approved', $approval['status']);

        $run = $this->runRepository->findByIdAndTenant((string)$result['run_id'], 'tenant-2');
        self::assertNotNull($run);
        self::assertSame('approved', $run->getStatus());

        $decisionCount = (int)$this->entityManager
            ->createQuery('SELECT COUNT(d.id) FROM ' . QuoteApprovalDecision::class . ' d')
            ->getSingleScalarResult();
        $trailCount = (int)$this->entityManager
            ->createQuery('SELECT COUNT(e.id) FROM ' . QuoteDecisionTrailEntry::class . ' e')
            ->getSingleScalarResult();

        self::assertSame(1, $decisionCount);
        self::assertSame(4, $trailCount);
    }

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        parent::tearDown();
    }
}
