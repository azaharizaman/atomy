<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Tests\Unit\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateQueryInterface;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\ApprovalOperations\DTOs\RecordApprovalDecisionCommand;
use Nexus\ApprovalOperations\DTOs\StartOperationalApprovalCommand;
use Nexus\ApprovalOperations\Exceptions\ApprovalTemplateNotFoundException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;
use Nexus\ApprovalOperations\Services\ApprovalProcessCoordinator;
use Nexus\ApprovalOperations\Services\ApprovalTemplateResolver;
use Nexus\Common\Contracts\UlidInterface;
use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use PHPUnit\Framework\TestCase;

final class ApprovalProcessCoordinatorTest extends TestCase
{
    public function testStartPassesTenantToTemplateQueryAndPolicyEngine(): void
    {
        $tenantId = '01hzyd8yq1v9zq8x9zq8x9zq8';
        $template = new ApprovalTemplateReadModel(
            id: 'tpl-1',
            tenantId: $tenantId,
            subjectType: 'x',
            workflowDefinitionId: 'wf-def',
            policyId: 'pol-1',
            policyVersion: 'v1',
            templateVersion: 1,
        );

        $templateQuery = $this->createMock(ApprovalTemplateQueryInterface::class);
        $templateQuery->expects(self::once())
            ->method('findBySubjectType')
            ->with($tenantId, 'x')
            ->willReturn($template);

        $policyEngine = $this->createMock(PolicyEngineInterface::class);
        $policyEngine->expects(self::once())
            ->method('evaluate')
            ->with(self::callback(static function (PolicyRequest $r) use ($tenantId): bool {
                return $r->tenantId->value === $tenantId
                    && $r->policyId->value === 'pol-1'
                    && $r->action === 'operational_approval.start';
            }))
            ->willReturn(new PolicyDecision(
                outcome: DecisionOutcome::Allow,
                matchedRuleIds: [],
                reasonCodes: [],
                obligations: [],
                traceId: 'trace',
            ));

        $bridge = $this->createMock(OperationalWorkflowBridgeInterface::class);
        $bridge->expects(self::once())
            ->method('startWorkflow')
            ->with(
                $tenantId,
                'wf-def',
                self::isInstanceOf(ApprovalSubjectRef::class),
                self::anything(),
            )
            ->willReturn('wf-instance-1');

        $persist = $this->createMock(ApprovalInstancePersistInterface::class);
        $persist->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (ApprovalInstanceReadModel $i) use ($tenantId): bool {
                return $i->tenantId === $tenantId && $i->workflowInstanceId === 'wf-instance-1';
            }));

        $ulid = $this->createMock(UlidInterface::class);
        $ulid->method('generate')->willReturn('01hzyd8yq1v9zq8x9zq8x9zq9');

        $coordinator = new ApprovalProcessCoordinator(
            new ApprovalTemplateResolver($templateQuery),
            $persist,
            $this->createMock(ApprovalInstanceQueryInterface::class),
            $policyEngine,
            $bridge,
            $ulid,
            $this->createMock(ApprovalCommentPersistInterface::class),
        );

        $result = $coordinator->start(new StartOperationalApprovalCommand(
            tenantId: $tenantId,
            subject: new ApprovalSubjectRef('x', 'sub-1'),
            initiatorPrincipalId: 'user-1',
        ));

        self::assertSame('01hzyd8yq1v9zq8x9zq8x9zq9', $result->instanceId);
        self::assertSame('wf-instance-1', $result->workflowInstanceId);
    }

    public function testStartThrowsWhenTemplateMissing(): void
    {
        $templateQuery = $this->createMock(ApprovalTemplateQueryInterface::class);
        $templateQuery->method('findBySubjectType')->willReturn(null);

        $coordinator = new ApprovalProcessCoordinator(
            new ApprovalTemplateResolver($templateQuery),
            $this->createMock(ApprovalInstancePersistInterface::class),
            $this->createMock(ApprovalInstanceQueryInterface::class),
            $this->createMock(PolicyEngineInterface::class),
            $this->createMock(OperationalWorkflowBridgeInterface::class),
            $this->createMock(UlidInterface::class),
            $this->createMock(ApprovalCommentPersistInterface::class),
        );

        $this->expectException(ApprovalTemplateNotFoundException::class);
        $coordinator->start(new StartOperationalApprovalCommand(
            tenantId: 't1',
            subject: new ApprovalSubjectRef('missing', '1'),
            initiatorPrincipalId: 'u1',
        ));
    }

    public function testRecordDecisionThrowsWhenInstanceMissing(): void
    {
        $query = $this->createMock(ApprovalInstanceQueryInterface::class);
        $query->expects(self::once())
            ->method('findById')
            ->with('t1', 'inst-1')
            ->willReturn(null);

        $coordinator = new ApprovalProcessCoordinator(
            new ApprovalTemplateResolver($this->createMock(ApprovalTemplateQueryInterface::class)),
            $this->createMock(ApprovalInstancePersistInterface::class),
            $query,
            $this->createMock(PolicyEngineInterface::class),
            $this->createMock(OperationalWorkflowBridgeInterface::class),
            $this->createMock(UlidInterface::class),
            $this->createMock(ApprovalCommentPersistInterface::class),
        );

        $this->expectException(OperationalApprovalNotFoundException::class);
        $coordinator->recordDecision(new RecordApprovalDecisionCommand(
            tenantId: 't1',
            instanceId: 'inst-1',
            actorPrincipalId: 'u1',
            decision: OperationalApprovalDecision::Approve,
        ));
    }
}
