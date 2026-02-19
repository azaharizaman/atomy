<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Services;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\Exceptions\InvalidStageTransitionException;
use Nexus\CRM\Services\StageTransitionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class StageTransitionServiceTest extends TestCase
{
    private OpportunityPersistInterface&MockObject $persistMock;
    private OpportunityInterface&MockObject $opportunityMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistMock = $this->createMock(OpportunityPersistInterface::class);
        $this->opportunityMock = $this->createMock(OpportunityInterface::class);
    }

    #[Test]
    public function it_creates_with_persist_interface(): void
    {
        $service = new StageTransitionService($this->persistMock);

        $this->assertInstanceOf(StageTransitionService::class, $service);
    }

    #[Test]
    public function it_advances_opportunity_to_next_stage(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $advancedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('moveToStage')
            ->with('opp-123', OpportunityStage::Qualification)
            ->willReturn($advancedOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->advance($this->opportunityMock);

        $this->assertSame($advancedOpportunity, $result);
    }

    #[Test]
    public function it_throws_exception_when_advancing_closed_won_opportunity(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);
        $this->expectExceptionMessage('Cannot transition: opportunity is already in final stage Closed Won');

        $service->advance($this->opportunityMock);
    }

    #[Test]
    public function it_throws_exception_when_advancing_closed_lost_opportunity(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedLost);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);
        $this->expectExceptionMessage('Cannot transition: opportunity is already in final stage Closed Lost');

        $service->advance($this->opportunityMock);
    }

    #[Test]
    public function it_moves_opportunity_to_specific_stage(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $movedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('moveToStage')
            ->with('opp-123', OpportunityStage::Qualification)
            ->willReturn($movedOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->moveToStage($this->opportunityMock, OpportunityStage::Qualification);

        $this->assertSame($movedOpportunity, $result);
    }

    #[Test]
    public function it_throws_exception_when_skipping_stages(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);
        $this->expectExceptionMessage('Cannot skip stages');

        $service->moveToStage($this->opportunityMock, OpportunityStage::Proposal);
    }

    #[Test]
    public function it_throws_exception_when_going_backwards(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);
        $this->expectExceptionMessage('Cannot transition backwards');

        $service->moveToStage($this->opportunityMock, OpportunityStage::Qualification);
    }

    #[Test]
    public function it_marks_opportunity_as_won(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);
        $this->opportunityMock->method('isLost')->willReturn(false);

        $wonOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsWon')
            ->with('opp-123', null)
            ->willReturn($wonOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->markAsWon($this->opportunityMock);

        $this->assertSame($wonOpportunity, $result);
    }

    #[Test]
    public function it_marks_opportunity_as_won_with_actual_value(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);
        $this->opportunityMock->method('isLost')->willReturn(false);

        $wonOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsWon')
            ->with('opp-123', 50000)
            ->willReturn($wonOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->markAsWon($this->opportunityMock, 50000);

        $this->assertSame($wonOpportunity, $result);
    }

    #[Test]
    public function it_throws_exception_when_marking_lost_opportunity_as_won(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedLost);
        $this->opportunityMock->method('isLost')->willReturn(true);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);

        $service->markAsWon($this->opportunityMock);
    }

    #[Test]
    public function it_marks_opportunity_as_lost(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);
        $this->opportunityMock->method('isWon')->willReturn(false);

        $lostOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsLost')
            ->with('opp-123', 'Budget constraints')
            ->willReturn($lostOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->markAsLost($this->opportunityMock, 'Budget constraints');

        $this->assertSame($lostOpportunity, $result);
    }

    #[Test]
    public function it_throws_exception_when_marking_won_opportunity_as_lost(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);
        $this->opportunityMock->method('isWon')->willReturn(true);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(InvalidStageTransitionException::class);

        $service->markAsLost($this->opportunityMock, 'Changed mind');
    }

    #[Test]
    public function it_reopens_closed_opportunity(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $reopenedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('reopen')
            ->with('opp-123', OpportunityStage::Qualification)
            ->willReturn($reopenedOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->reopen($this->opportunityMock, OpportunityStage::Qualification);

        $this->assertSame($reopenedOpportunity, $result);
    }

    #[Test]
    public function it_throws_exception_when_reopening_open_opportunity(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is not closed and cannot be reopened');

        $service->reopen($this->opportunityMock, OpportunityStage::Qualification);
    }

    #[Test]
    public function it_throws_exception_when_reopening_to_closed_stage(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $service = new StageTransitionService($this->persistMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot reopen to a closed stage');

        $service->reopen($this->opportunityMock, OpportunityStage::ClosedLost);
    }

    #[Test]
    public function it_checks_if_opportunity_can_advance(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $service = new StageTransitionService($this->persistMock);

        $this->assertTrue($service->canAdvance($this->opportunityMock));
    }

    #[Test]
    public function it_returns_false_for_final_stage_advance(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $service = new StageTransitionService($this->persistMock);

        $this->assertFalse($service->canAdvance($this->opportunityMock));
    }

    #[Test]
    public function it_gets_valid_next_stages_for_open_opportunity(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $service = new StageTransitionService($this->persistMock);
        $validStages = $service->getValidNextStages($this->opportunityMock);

        $this->assertCount(3, $validStages);
        $this->assertContains(OpportunityStage::Qualification, $validStages);
        $this->assertContains(OpportunityStage::ClosedWon, $validStages);
        $this->assertContains(OpportunityStage::ClosedLost, $validStages);
    }

    #[Test]
    public function it_gets_valid_next_stages_for_negotiation_stage(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);

        $service = new StageTransitionService($this->persistMock);
        $validStages = $service->getValidNextStages($this->opportunityMock);

        $this->assertCount(3, $validStages);
        $this->assertContains(OpportunityStage::ClosedWon, $validStages);
        $this->assertContains(OpportunityStage::ClosedLost, $validStages);
    }

    #[Test]
    public function it_returns_empty_array_for_final_stages(): void
    {
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $service = new StageTransitionService($this->persistMock);
        $validStages = $service->getValidNextStages($this->opportunityMock);

        $this->assertEmpty($validStages);
    }

    #[Test]
    public function it_logs_advance_operation_when_logger_provided(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);

        $advancedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('moveToStage')->willReturn($advancedOpportunity);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Advancing opportunity stage',
                $this->callback(function (array $context) {
                    return $context['opportunity_id'] === 'opp-123'
                        && $context['from_stage'] === 'prospecting'
                        && $context['to_stage'] === 'qualification';
                })
            );

        $service = new StageTransitionService($this->persistMock, $loggerMock);
        $service->advance($this->opportunityMock);
    }

    #[Test]
    public function it_logs_mark_as_won_operation(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);
        $this->opportunityMock->method('isLost')->willReturn(false);

        $wonOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsWon')->willReturn($wonOpportunity);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Marking opportunity as won',
                $this->callback(function (array $context) {
                    return $context['opportunity_id'] === 'opp-123'
                        && $context['actual_value'] === 50000;
                })
            );

        $service = new StageTransitionService($this->persistMock, $loggerMock);
        $service->markAsWon($this->opportunityMock, 50000);
    }

    #[Test]
    public function it_logs_mark_as_lost_operation(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Negotiation);
        $this->opportunityMock->method('isWon')->willReturn(false);

        $lostOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsLost')->willReturn($lostOpportunity);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Marking opportunity as lost',
                $this->callback(function (array $context) {
                    return $context['opportunity_id'] === 'opp-123'
                        && $context['reason'] === 'Budget cut';
                })
            );

        $service = new StageTransitionService($this->persistMock, $loggerMock);
        $service->markAsLost($this->opportunityMock, 'Budget cut');
    }

    #[Test]
    public function it_logs_reopen_operation(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::ClosedWon);

        $reopenedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('reopen')->willReturn($reopenedOpportunity);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Reopening opportunity',
                $this->callback(function (array $context) {
                    return $context['opportunity_id'] === 'opp-123'
                        && $context['previous_stage'] === 'closed_won'
                        && $context['new_stage'] === 'negotiation';
                })
            );

        $service = new StageTransitionService($this->persistMock, $loggerMock);
        $service->reopen($this->opportunityMock, OpportunityStage::Negotiation);
    }

    #[Test]
    #[DataProvider('stageAdvancementProvider')]
    public function it_advances_through_all_stages_sequentially(
        OpportunityStage $currentStage,
        OpportunityStage $expectedNextStage
    ): void {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn($currentStage);

        $advancedOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('moveToStage')
            ->with('opp-123', $expectedNextStage)
            ->willReturn($advancedOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->advance($this->opportunityMock);

        $this->assertSame($advancedOpportunity, $result);
    }

    public static function stageAdvancementProvider(): array
    {
        return [
            'prospecting to qualification' => [OpportunityStage::Prospecting, OpportunityStage::Qualification],
            'qualification to needs analysis' => [OpportunityStage::Qualification, OpportunityStage::NeedsAnalysis],
            'needs analysis to proposal' => [OpportunityStage::NeedsAnalysis, OpportunityStage::Proposal],
            'proposal to negotiation' => [OpportunityStage::Proposal, OpportunityStage::Negotiation],
            'negotiation to closed won' => [OpportunityStage::Negotiation, OpportunityStage::ClosedWon],
        ];
    }

    #[Test]
    public function it_can_close_as_won_from_any_open_stage(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Prospecting);
        $this->opportunityMock->method('isLost')->willReturn(false);

        $wonOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsWon')->willReturn($wonOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->markAsWon($this->opportunityMock);

        $this->assertSame($wonOpportunity, $result);
    }

    #[Test]
    public function it_can_close_as_lost_from_any_open_stage(): void
    {
        $this->opportunityMock->method('getId')->willReturn('opp-123');
        $this->opportunityMock->method('getStage')->willReturn(OpportunityStage::Qualification);
        $this->opportunityMock->method('isWon')->willReturn(false);

        $lostOpportunity = $this->createMock(OpportunityInterface::class);
        $this->persistMock->method('markAsLost')->willReturn($lostOpportunity);

        $service = new StageTransitionService($this->persistMock);
        $result = $service->markAsLost($this->opportunityMock, 'Not interested');

        $this->assertSame($lostOpportunity, $result);
    }
}
