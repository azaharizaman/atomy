<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Enums;

use Nexus\CRM\Enums\OpportunityStage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OpportunityStageTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $cases = OpportunityStage::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(OpportunityStage::Prospecting, $cases);
        $this->assertContains(OpportunityStage::Qualification, $cases);
        $this->assertContains(OpportunityStage::NeedsAnalysis, $cases);
        $this->assertContains(OpportunityStage::Proposal, $cases);
        $this->assertContains(OpportunityStage::Negotiation, $cases);
        $this->assertContains(OpportunityStage::ClosedWon, $cases);
        $this->assertContains(OpportunityStage::ClosedLost, $cases);
    }

    #[Test]
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('prospecting', OpportunityStage::Prospecting->value);
        $this->assertSame('qualification', OpportunityStage::Qualification->value);
        $this->assertSame('needs_analysis', OpportunityStage::NeedsAnalysis->value);
        $this->assertSame('proposal', OpportunityStage::Proposal->value);
        $this->assertSame('negotiation', OpportunityStage::Negotiation->value);
        $this->assertSame('closed_won', OpportunityStage::ClosedWon->value);
        $this->assertSame('closed_lost', OpportunityStage::ClosedLost->value);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Prospecting', OpportunityStage::Prospecting->label());
        $this->assertSame('Qualification', OpportunityStage::Qualification->label());
        $this->assertSame('Needs Analysis', OpportunityStage::NeedsAnalysis->label());
        $this->assertSame('Proposal', OpportunityStage::Proposal->label());
        $this->assertSame('Negotiation', OpportunityStage::Negotiation->label());
        $this->assertSame('Closed Won', OpportunityStage::ClosedWon->label());
        $this->assertSame('Closed Lost', OpportunityStage::ClosedLost->label());
    }

    #[Test]
    #[DataProvider('defaultProbabilityProvider')]
    public function it_returns_correct_default_probabilities(OpportunityStage $stage, int $expectedProbability): void
    {
        $this->assertSame($expectedProbability, $stage->getDefaultProbability());
    }

    public static function defaultProbabilityProvider(): array
    {
        return [
            'prospecting has 10%' => [OpportunityStage::Prospecting, 10],
            'qualification has 20%' => [OpportunityStage::Qualification, 20],
            'needs analysis has 40%' => [OpportunityStage::NeedsAnalysis, 40],
            'proposal has 60%' => [OpportunityStage::Proposal, 60],
            'negotiation has 80%' => [OpportunityStage::Negotiation, 80],
            'closed won has 100%' => [OpportunityStage::ClosedWon, 100],
            'closed lost has 0%' => [OpportunityStage::ClosedLost, 0],
        ];
    }

    #[Test]
    #[DataProvider('positionProvider')]
    public function it_returns_correct_positions(OpportunityStage $stage, int $expectedPosition): void
    {
        $this->assertSame($expectedPosition, $stage->getPosition());
    }

    public static function positionProvider(): array
    {
        return [
            'prospecting is position 1' => [OpportunityStage::Prospecting, 1],
            'qualification is position 2' => [OpportunityStage::Qualification, 2],
            'needs analysis is position 3' => [OpportunityStage::NeedsAnalysis, 3],
            'proposal is position 4' => [OpportunityStage::Proposal, 4],
            'negotiation is position 5' => [OpportunityStage::Negotiation, 5],
            'closed won is position 6' => [OpportunityStage::ClosedWon, 6],
            'closed lost is position 7' => [OpportunityStage::ClosedLost, 7],
        ];
    }

    #[Test]
    #[DataProvider('openStageProvider')]
    public function it_identifies_open_stages_correctly(OpportunityStage $stage, bool $expectedIsOpen): void
    {
        $this->assertSame($expectedIsOpen, $stage->isOpen());
    }

    public static function openStageProvider(): array
    {
        return [
            'prospecting is open' => [OpportunityStage::Prospecting, true],
            'qualification is open' => [OpportunityStage::Qualification, true],
            'needs analysis is open' => [OpportunityStage::NeedsAnalysis, true],
            'proposal is open' => [OpportunityStage::Proposal, true],
            'negotiation is open' => [OpportunityStage::Negotiation, true],
            'closed won is not open' => [OpportunityStage::ClosedWon, false],
            'closed lost is not open' => [OpportunityStage::ClosedLost, false],
        ];
    }

    #[Test]
    public function it_identifies_won_stage_correctly(): void
    {
        $this->assertTrue(OpportunityStage::ClosedWon->isWon());
        $this->assertFalse(OpportunityStage::ClosedLost->isWon());
        $this->assertFalse(OpportunityStage::Prospecting->isWon());
    }

    #[Test]
    public function it_identifies_lost_stage_correctly(): void
    {
        $this->assertTrue(OpportunityStage::ClosedLost->isLost());
        $this->assertFalse(OpportunityStage::ClosedWon->isLost());
        $this->assertFalse(OpportunityStage::Prospecting->isLost());
    }

    #[Test]
    #[DataProvider('finalStageProvider')]
    public function it_identifies_final_stages_correctly(OpportunityStage $stage, bool $expectedIsFinal): void
    {
        $this->assertSame($expectedIsFinal, $stage->isFinal());
    }

    public static function finalStageProvider(): array
    {
        return [
            'prospecting is not final' => [OpportunityStage::Prospecting, false],
            'qualification is not final' => [OpportunityStage::Qualification, false],
            'needs analysis is not final' => [OpportunityStage::NeedsAnalysis, false],
            'proposal is not final' => [OpportunityStage::Proposal, false],
            'negotiation is not final' => [OpportunityStage::Negotiation, false],
            'closed won is final' => [OpportunityStage::ClosedWon, true],
            'closed lost is final' => [OpportunityStage::ClosedLost, true],
        ];
    }

    #[Test]
    public function it_returns_correct_next_stages(): void
    {
        $this->assertSame(OpportunityStage::Qualification, OpportunityStage::Prospecting->getNextStage());
        $this->assertSame(OpportunityStage::NeedsAnalysis, OpportunityStage::Qualification->getNextStage());
        $this->assertSame(OpportunityStage::Proposal, OpportunityStage::NeedsAnalysis->getNextStage());
        $this->assertSame(OpportunityStage::Negotiation, OpportunityStage::Proposal->getNextStage());
        $this->assertSame(OpportunityStage::ClosedWon, OpportunityStage::Negotiation->getNextStage());
    }

    #[Test]
    public function it_returns_null_for_final_stages_next_stage(): void
    {
        $this->assertNull(OpportunityStage::ClosedWon->getNextStage());
        $this->assertNull(OpportunityStage::ClosedLost->getNextStage());
    }

    #[Test]
    #[DataProvider('canAdvanceProvider')]
    public function it_identifies_advanceable_stages_correctly(OpportunityStage $stage, bool $expectedCanAdvance): void
    {
        $this->assertSame($expectedCanAdvance, $stage->canAdvance());
    }

    public static function canAdvanceProvider(): array
    {
        return [
            'prospecting can advance' => [OpportunityStage::Prospecting, true],
            'qualification can advance' => [OpportunityStage::Qualification, true],
            'needs analysis can advance' => [OpportunityStage::NeedsAnalysis, true],
            'proposal can advance' => [OpportunityStage::Proposal, true],
            'negotiation can advance' => [OpportunityStage::Negotiation, true],
            'closed won cannot advance' => [OpportunityStage::ClosedWon, false],
            'closed lost cannot advance' => [OpportunityStage::ClosedLost, false],
        ];
    }

    #[Test]
    public function it_returns_all_open_stages_in_order(): void
    {
        $openStages = OpportunityStage::openStages();

        $this->assertCount(5, $openStages);
        $this->assertSame(OpportunityStage::Prospecting, $openStages[0]);
        $this->assertSame(OpportunityStage::Qualification, $openStages[1]);
        $this->assertSame(OpportunityStage::NeedsAnalysis, $openStages[2]);
        $this->assertSame(OpportunityStage::Proposal, $openStages[3]);
        $this->assertSame(OpportunityStage::Negotiation, $openStages[4]);
    }

    #[Test]
    public function open_stages_are_sorted_by_position(): void
    {
        $openStages = OpportunityStage::openStages();
        $positions = array_map(fn ($stage) => $stage->getPosition(), $openStages);

        $sortedPositions = $positions;
        sort($sortedPositions);

        $this->assertSame($sortedPositions, $positions);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $stage = OpportunityStage::from('negotiation');

        $this->assertSame(OpportunityStage::Negotiation, $stage);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(\ValueError::class);

        OpportunityStage::from('invalid_stage');
    }

    #[Test]
    public function it_can_try_from_string_safely(): void
    {
        $stage = OpportunityStage::tryFrom('proposal');

        $this->assertSame(OpportunityStage::Proposal, $stage);
    }

    #[Test]
    public function it_returns_null_for_invalid_try_from(): void
    {
        $stage = OpportunityStage::tryFrom('invalid_stage');

        $this->assertNull($stage);
    }

    #[Test]
    public function probabilities_increase_with_stage_progression(): void
    {
        $openStages = OpportunityStage::openStages();
        $previousProbability = 0;

        foreach ($openStages as $stage) {
            $this->assertGreaterThan(
                $previousProbability,
                $stage->getDefaultProbability(),
                sprintf('%s should have higher probability than previous stage', $stage->name)
            );
            $previousProbability = $stage->getDefaultProbability();
        }
    }
}
