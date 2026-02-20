<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\ValueObjects;

use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\ValueObjects\PipelineStage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PipelineStageTest extends TestCase
{
    #[Test]
    public function it_creates_pipeline_stage_with_required_fields(): void
    {
        $stage = new PipelineStage(
            name: 'Prospecting',
            position: 1,
            probability: 10
        );

        $this->assertSame('Prospecting', $stage->getName());
        $this->assertSame(1, $stage->getPosition());
        $this->assertSame(10, $stage->getProbability());
    }

    #[Test]
    public function it_creates_pipeline_stage_with_optional_fields(): void
    {
        $metadata = ['color' => 'blue', 'owner_required' => true];

        $stage = new PipelineStage(
            name: 'Negotiation',
            position: 5,
            probability: 80,
            description: 'Final negotiation stage',
            metadata: $metadata
        );

        $this->assertSame('Negotiation', $stage->getName());
        $this->assertSame(5, $stage->getPosition());
        $this->assertSame(80, $stage->getProbability());
        $this->assertSame('Final negotiation stage', $stage->getDescription());
        $this->assertSame($metadata, $stage->metadata);
    }

    #[Test]
    public function it_throws_exception_for_position_less_than_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stage position must be at least 1');

        new PipelineStage('Test', 0, 50);
    }

    #[Test]
    public function it_throws_exception_for_negative_probability(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Probability must be between 0 and 100');

        new PipelineStage('Test', 1, -1);
    }

    #[Test]
    public function it_throws_exception_for_probability_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Probability must be between 0 and 100');

        new PipelineStage('Test', 1, 101);
    }

    #[Test]
    public function it_accepts_boundary_values(): void
    {
        $minProbability = new PipelineStage('Lost', 1, 0);
        $maxProbability = new PipelineStage('Won', 2, 100);

        $this->assertSame(0, $minProbability->getProbability());
        $this->assertSame(100, $maxProbability->getProbability());
    }

    #[Test]
    public function it_creates_from_opportunity_stage_enum(): void
    {
        $stage = PipelineStage::fromEnum(OpportunityStage::Prospecting);

        $this->assertSame('Prospecting', $stage->getName());
        $this->assertSame(1, $stage->getPosition());
        $this->assertSame(10, $stage->getProbability());
    }

    #[Test]
    #[DataProvider('opportunityStageProvider')]
    public function it_creates_correct_stage_from_each_enum_case(OpportunityStage $enumStage, string $expectedName, int $expectedPosition, int $expectedProbability): void
    {
        $stage = PipelineStage::fromEnum($enumStage);

        $this->assertSame($expectedName, $stage->getName());
        $this->assertSame($expectedPosition, $stage->getPosition());
        $this->assertSame($expectedProbability, $stage->getProbability());
    }

    public static function opportunityStageProvider(): array
    {
        return [
            'prospecting' => [OpportunityStage::Prospecting, 'Prospecting', 1, 10],
            'qualification' => [OpportunityStage::Qualification, 'Qualification', 2, 20],
            'needs analysis' => [OpportunityStage::NeedsAnalysis, 'Needs Analysis', 3, 40],
            'proposal' => [OpportunityStage::Proposal, 'Proposal', 4, 60],
            'negotiation' => [OpportunityStage::Negotiation, 'Negotiation', 5, 80],
            'closed won' => [OpportunityStage::ClosedWon, 'Closed Won', 6, 100],
            'closed lost' => [OpportunityStage::ClosedLost, 'Closed Lost', 7, 0],
        ];
    }

    #[Test]
    public function it_returns_probability_as_decimal(): void
    {
        $stage = new PipelineStage('Test', 1, 75);

        $this->assertSame(0.75, $stage->getProbabilityDecimal());
    }

    #[Test]
    public function it_handles_zero_probability_decimal(): void
    {
        $stage = new PipelineStage('Lost', 1, 0);

        $this->assertSame(0.0, $stage->getProbabilityDecimal());
    }

    #[Test]
    public function it_handles_hundred_probability_decimal(): void
    {
        $stage = new PipelineStage('Won', 1, 100);

        $this->assertSame(1.0, $stage->getProbabilityDecimal());
    }

    #[Test]
    public function it_returns_null_description_when_not_set(): void
    {
        $stage = new PipelineStage('Test', 1, 50);

        $this->assertNull($stage->getDescription());
    }

    #[Test]
    public function it_gets_metadata_value(): void
    {
        $stage = new PipelineStage(
            'Test',
            1,
            50,
            null,
            ['color' => 'red', 'priority' => 'high']
        );

        $this->assertSame('red', $stage->getMetadata('color'));
        $this->assertSame('high', $stage->getMetadata('priority'));
    }

    #[Test]
    public function it_returns_default_for_missing_metadata(): void
    {
        $stage = new PipelineStage('Test', 1, 50);

        $this->assertNull($stage->getMetadata('nonexistent'));
        $this->assertSame('default', $stage->getMetadata('nonexistent', 'default'));
    }

    #[Test]
    #[DataProvider('finalStageProvider')]
    public function it_identifies_final_stages_correctly(int $probability, bool $expectedIsFinal): void
    {
        $stage = new PipelineStage('Test', 1, $probability);

        $this->assertSame($expectedIsFinal, $stage->isFinal());
    }

    public static function finalStageProvider(): array
    {
        return [
            '0% probability is final' => [0, true],
            '100% probability is final' => [100, true],
            '50% probability is not final' => [50, false],
            '10% probability is not final' => [10, false],
            '99% probability is not final' => [99, false],
            '1% probability is not final' => [1, false],
        ];
    }

    #[Test]
    public function it_identifies_win_stage(): void
    {
        $winStage = new PipelineStage('Won', 1, 100);
        $otherStage = new PipelineStage('Other', 1, 80);

        $this->assertTrue($winStage->isWinStage());
        $this->assertFalse($otherStage->isWinStage());
    }

    #[Test]
    public function it_identifies_loss_stage(): void
    {
        $lossStage = new PipelineStage('Lost', 1, 0);
        $otherStage = new PipelineStage('Other', 1, 20);

        $this->assertTrue($lossStage->isLossStage());
        $this->assertFalse($otherStage->isLossStage());
    }

    #[Test]
    public function it_compares_positions_correctly(): void
    {
        $earlyStage = new PipelineStage('Early', 1, 10);
        $lateStage = new PipelineStage('Late', 5, 80);

        $this->assertTrue($earlyStage->isBefore($lateStage));
        $this->assertFalse($earlyStage->isAfter($lateStage));
        $this->assertFalse($lateStage->isBefore($earlyStage));
        $this->assertTrue($lateStage->isAfter($earlyStage));
    }

    #[Test]
    public function it_creates_new_stage_with_updated_probability(): void
    {
        $original = new PipelineStage(
            'Test',
            3,
            40,
            'Description',
            ['key' => 'value']
        );

        $updated = $original->withProbability(60);

        $this->assertSame(60, $updated->getProbability());
        $this->assertSame($original->getName(), $updated->getName());
        $this->assertSame($original->getPosition(), $updated->getPosition());
        $this->assertSame($original->getDescription(), $updated->getDescription());
        $this->assertSame($original->metadata, $updated->metadata);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $stage = new PipelineStage(
            'Negotiation',
            5,
            80,
            'Final stage',
            ['color' => 'green']
        );

        $array = $stage->toArray();

        $this->assertSame([
            'name' => 'Negotiation',
            'position' => 5,
            'probability' => 80,
            'description' => 'Final stage',
            'metadata' => ['color' => 'green'],
        ], $array);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $stage = new PipelineStage('Prospecting', 1, 10);

        $this->assertSame('Prospecting', (string) $stage);
        $this->assertSame('Prospecting', $stage->__toString());
    }

    #[Test]
    public function it_is_readonly(): void
    {
        $stage = new PipelineStage('Test', 1, 50, 'Desc', ['key' => 'val']);

        $this->assertSame('Test', $stage->name);
        $this->assertSame(1, $stage->position);
        $this->assertSame(50, $stage->probability);
        $this->assertSame('Desc', $stage->description);
        $this->assertSame(['key' => 'val'], $stage->metadata);
    }

    #[Test]
    public function win_and_loss_stages_are_both_final(): void
    {
        $winStage = new PipelineStage('Won', 1, 100);
        $lossStage = new PipelineStage('Lost', 1, 0);

        $this->assertTrue($winStage->isFinal());
        $this->assertTrue($lossStage->isFinal());
        $this->assertTrue($winStage->isWinStage());
        $this->assertTrue($lossStage->isLossStage());
        $this->assertFalse($winStage->isLossStage());
        $this->assertFalse($lossStage->isWinStage());
    }
}
