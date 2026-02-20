<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\ValueObjects\CashSweepInstruction;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CashSweepInstructionTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD')
        );

        $this->assertEquals('ACC-001', $instruction->sourceAccountId);
        $this->assertEquals('ACC-002', $instruction->targetAccountId);
        $this->assertEquals(10000, $instruction->sweepThreshold->getAmount());
        $this->assertEquals(5000, $instruction->sweepAmount->getAmount());
        $this->assertTrue($instruction->retainMinimum);
        $this->assertNull($instruction->retainAmount);
    }

    public function test_creates_with_retain_amount(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD'),
            retainMinimum: true,
            retainAmount: Money::of(2000, 'USD')
        );

        $this->assertTrue($instruction->retainMinimum);
        $this->assertEquals(2000, $instruction->retainAmount->getAmount());
    }

    public function test_throws_exception_on_currency_mismatch_threshold_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sweep threshold and amount must be in the same currency');

        new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'EUR')
        );
    }

    public function test_throws_exception_on_currency_mismatch_retain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Retain amount must be in the same currency as sweep threshold');

        new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD'),
            retainMinimum: true,
            retainAmount: Money::of(2000, 'EUR')
        );
    }

    public function test_from_array_creates_instruction(): void
    {
        $instruction = CashSweepInstruction::fromArray([
            'source_account_id' => 'ACC-001',
            'target_account_id' => 'ACC-002',
            'sweep_threshold' => 10000,
            'sweep_amount' => 5000,
            'retain_minimum' => false,
            'currency' => 'USD',
        ]);

        $this->assertEquals('ACC-001', $instruction->sourceAccountId);
        $this->assertEquals('ACC-002', $instruction->targetAccountId);
        $this->assertFalse($instruction->retainMinimum);
    }

    public function test_to_array_returns_array(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD')
        );

        $array = $instruction->toArray();

        $this->assertEquals('ACC-001', $array['sourceAccountId']);
        $this->assertEquals('ACC-002', $array['targetAccountId']);
    }

    public function test_get_currency_returns_currency(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'EUR'),
            sweepAmount: Money::of(5000, 'EUR')
        );

        $this->assertEquals('EUR', $instruction->getCurrency());
    }

    public function test_is_full_sweep_returns_true_when_no_retain(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD'),
            retainMinimum: false
        );

        $this->assertTrue($instruction->isFullSweep());
    }

    public function test_is_full_sweep_returns_false_when_retain(): void
    {
        $instruction = new CashSweepInstruction(
            sourceAccountId: 'ACC-001',
            targetAccountId: 'ACC-002',
            sweepThreshold: Money::of(10000, 'USD'),
            sweepAmount: Money::of(5000, 'USD'),
            retainMinimum: true,
            retainAmount: Money::of(2000, 'USD')
        );

        $this->assertFalse($instruction->isFullSweep());
    }
}
