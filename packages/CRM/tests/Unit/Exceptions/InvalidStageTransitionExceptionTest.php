<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Exceptions;

use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\Exceptions\CRMException;
use Nexus\CRM\Exceptions\InvalidStageTransitionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvalidStageTransitionExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_crm_exception(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Prospecting,
            OpportunityStage::ClosedWon
        );

        $this->assertInstanceOf(CRMException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_with_from_and_to_stages(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Prospecting,
            OpportunityStage::Negotiation
        );

        $this->assertSame(OpportunityStage::Prospecting, $exception->from);
        $this->assertSame(OpportunityStage::Negotiation, $exception->to);
    }

    #[Test]
    public function it_generates_correct_message(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Prospecting,
            OpportunityStage::Negotiation
        );

        $this->assertSame(
            'Invalid stage transition from Prospecting to Negotiation',
            $exception->getMessage()
        );
    }

    #[Test]
    #[DataProvider('stageTransitionProvider')]
    public function it_generates_correct_messages_for_various_transitions(
        OpportunityStage $from,
        OpportunityStage $to,
        string $expectedMessage
    ): void {
        $exception = new InvalidStageTransitionException($from, $to);

        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public static function stageTransitionProvider(): array
    {
        return [
            'prospecting to negotiation' => [
                OpportunityStage::Prospecting,
                OpportunityStage::Negotiation,
                'Invalid stage transition from Prospecting to Negotiation',
            ],
            'qualification to closed won' => [
                OpportunityStage::Qualification,
                OpportunityStage::ClosedWon,
                'Invalid stage transition from Qualification to Closed Won',
            ],
            'closed won to prospecting' => [
                OpportunityStage::ClosedWon,
                OpportunityStage::Prospecting,
                'Invalid stage transition from Closed Won to Prospecting',
            ],
            'closed lost to negotiation' => [
                OpportunityStage::ClosedLost,
                OpportunityStage::Negotiation,
                'Invalid stage transition from Closed Lost to Negotiation',
            ],
        ];
    }

    #[Test]
    public function it_returns_from_stage(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Proposal,
            OpportunityStage::Prospecting
        );

        $this->assertSame(OpportunityStage::Proposal, $exception->getFromStage());
    }

    #[Test]
    public function it_returns_to_stage(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Proposal,
            OpportunityStage::Prospecting
        );

        $this->assertSame(OpportunityStage::Prospecting, $exception->getToStage());
    }

    #[Test]
    public function it_creates_cannot_skip_stages_exception(): void
    {
        $exception = InvalidStageTransitionException::cannotSkipStages(
            OpportunityStage::Prospecting,
            OpportunityStage::Proposal
        );

        $this->assertSame(
            'Cannot skip stages: transition from Prospecting to Proposal is not allowed. Use sequential stage advancement.',
            $exception->getMessage()
        );
        $this->assertSame(OpportunityStage::Prospecting, $exception->from);
        $this->assertSame(OpportunityStage::Proposal, $exception->to);
    }

    #[Test]
    public function it_creates_opportunity_already_closed_exception(): void
    {
        $exception = InvalidStageTransitionException::opportunityAlreadyClosed(
            OpportunityStage::ClosedWon
        );

        $this->assertSame(
            'Cannot transition: opportunity is already in final stage Closed Won',
            $exception->getMessage()
        );
        $this->assertSame(OpportunityStage::ClosedWon, $exception->from);
        $this->assertSame(OpportunityStage::ClosedWon, $exception->to);
    }

    #[Test]
    public function it_creates_cannot_go_backwards_exception(): void
    {
        $exception = InvalidStageTransitionException::cannotGoBackwards(
            OpportunityStage::Negotiation,
            OpportunityStage::Qualification
        );

        $this->assertSame(
            'Cannot transition backwards from Negotiation to Qualification. Use reopen functionality instead.',
            $exception->getMessage()
        );
        $this->assertSame(OpportunityStage::Negotiation, $exception->from);
        $this->assertSame(OpportunityStage::Qualification, $exception->to);
    }

    #[Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $this->expectException(InvalidStageTransitionException::class);
        $this->expectExceptionMessage('Invalid stage transition from Proposal to Prospecting');

        throw new InvalidStageTransitionException(
            OpportunityStage::Proposal,
            OpportunityStage::Prospecting
        );
    }

    #[Test]
    public function it_can_be_caught_as_crm_exception(): void
    {
        $caught = false;

        try {
            throw new InvalidStageTransitionException(
                OpportunityStage::Negotiation,
                OpportunityStage::Qualification
            );
        } catch (CRMException $e) {
            $caught = true;
            $this->assertInstanceOf(InvalidStageTransitionException::class, $e);
        }

        $this->assertTrue($caught);
    }

    #[Test]
    public function it_preserves_stack_trace(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Prospecting,
            OpportunityStage::ClosedWon
        );

        $this->assertNotEmpty($exception->getTrace());
        $this->assertStringContainsString(__FUNCTION__, $exception->getTraceAsString());
    }

    #[Test]
    public function it_handles_same_stage_transition(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Proposal,
            OpportunityStage::Proposal
        );

        $this->assertSame(
            'Invalid stage transition from Proposal to Proposal',
            $exception->getMessage()
        );
    }

    #[Test]
    public function factory_methods_create_correct_exception_type(): void
    {
        $skipException = InvalidStageTransitionException::cannotSkipStages(
            OpportunityStage::Prospecting,
            OpportunityStage::Negotiation
        );
        $closedException = InvalidStageTransitionException::opportunityAlreadyClosed(
            OpportunityStage::ClosedLost
        );
        $backwardsException = InvalidStageTransitionException::cannotGoBackwards(
            OpportunityStage::Proposal,
            OpportunityStage::Prospecting
        );

        $this->assertInstanceOf(InvalidStageTransitionException::class, $skipException);
        $this->assertInstanceOf(InvalidStageTransitionException::class, $closedException);
        $this->assertInstanceOf(InvalidStageTransitionException::class, $backwardsException);
    }

    #[Test]
    public function it_returns_string_representation(): void
    {
        $exception = new InvalidStageTransitionException(
            OpportunityStage::Qualification,
            OpportunityStage::ClosedWon
        );

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('Invalid stage transition', $stringRepresentation);
        $this->assertStringContainsString(InvalidStageTransitionException::class, $stringRepresentation);
    }
}
