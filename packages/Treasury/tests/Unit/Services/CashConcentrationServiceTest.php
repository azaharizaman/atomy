<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Services\CashConcentrationService;
use Nexus\Treasury\ValueObjects\CashSweepInstruction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CashConcentrationServiceTest extends TestCase
{
    private CashConcentrationService $service;
    private MockObject $liquidityPoolQuery;
    private MockObject $cashManagementProvider;

    protected function setUp(): void
    {
        $this->liquidityPoolQuery = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->cashManagementProvider = $this->createMock(CashManagementProviderInterface::class);

        $this->service = new CashConcentrationService(
            $this->liquidityPoolQuery,
            $this->cashManagementProvider,
            new NullLogger()
        );
    }

    public function test_generate_sweep_instructions_creates_instructions_for_excess_accounts(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturnMap([
                ['acc-001', 15000.0],
                ['acc-002', 4000.0],
                ['acc-003', 12000.0],
            ]);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $instructions = $this->service->generateSweepInstructions(
            tenantId: 'tenant-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD'),
            sourceAccountIds: ['acc-001', 'acc-002', 'acc-003']
        );

        $this->assertCount(2, $instructions);
        $this->assertInstanceOf(CashSweepInstruction::class, $instructions[0]);
    }

    public function test_generate_sweep_instructions_skips_accounts_below_threshold(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturnMap([
                ['acc-001', 3000.0],
                ['acc-002', 4000.0],
            ]);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $instructions = $this->service->generateSweepInstructions(
            tenantId: 'tenant-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD'),
            sourceAccountIds: ['acc-001', 'acc-002']
        );

        $this->assertCount(0, $instructions);
    }

    public function test_generate_sweep_instructions_skips_different_currency(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(15000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('EUR');

        $instructions = $this->service->generateSweepInstructions(
            tenantId: 'tenant-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD'),
            sourceAccountIds: ['acc-001']
        );

        $this->assertCount(0, $instructions);
    }

    public function test_generate_sweep_instructions_with_retain_amount(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(20000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $instructions = $this->service->generateSweepInstructions(
            tenantId: 'tenant-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD'),
            sourceAccountIds: ['acc-001'],
            retainAmount: Money::of(3000, 'USD')
        );

        $this->assertCount(1, $instructions);
        $this->assertEquals(12000.0, $instructions[0]->sweepAmount->getAmount());
    }

    public function test_execute_sweep_returns_true_with_provider(): void
    {
        $instruction = $this->createInstruction();

        $result = $this->service->executeSweep($instruction);

        $this->assertTrue($result);
    }

    public function test_execute_sweep_returns_false_without_provider(): void
    {
        $service = new CashConcentrationService(
            $this->liquidityPoolQuery,
            null,
            new NullLogger()
        );

        $instruction = $this->createInstruction();

        $result = $service->executeSweep($instruction);

        $this->assertFalse($result);
    }

    public function test_execute_all_sweeps_processes_all_instructions(): void
    {
        $instruction1 = $this->createInstruction();
        $instruction2 = $this->createInstruction();

        $results = $this->service->executeAllSweeps([$instruction1, $instruction2]);

        $this->assertCount(2, $results['successful']);
        $this->assertCount(0, $results['failed']);
    }

    public function test_execute_all_sweeps_with_mixed_results(): void
    {
        $service = new CashConcentrationService(
            $this->liquidityPoolQuery,
            null,
            new NullLogger()
        );

        $instruction1 = $this->createInstruction();
        $instruction2 = $this->createInstruction();

        $results = $service->executeAllSweeps([$instruction1, $instruction2]);

        $this->assertCount(0, $results['successful']);
        $this->assertCount(2, $results['failed']);
    }

    public function test_calculate_optimal_sweep_returns_instruction(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(20000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $result = $this->service->calculateOptimalSweep(
            sourceAccountId: 'acc-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD')
        );

        $this->assertNotNull($result);
        $this->assertInstanceOf(CashSweepInstruction::class, $result);
        $this->assertEquals(15000.0, $result->sweepAmount->getAmount());
    }

    public function test_calculate_optimal_sweep_returns_null_for_low_balance(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(3000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $result = $this->service->calculateOptimalSweep(
            sourceAccountId: 'acc-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD')
        );

        $this->assertNull($result);
    }

    public function test_calculate_optimal_sweep_returns_null_for_currency_mismatch(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(20000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('EUR');

        $result = $this->service->calculateOptimalSweep(
            sourceAccountId: 'acc-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD')
        );

        $this->assertNull($result);
    }

    public function test_calculate_optimal_sweep_with_minimum_retained(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturn(20000.0);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $result = $this->service->calculateOptimalSweep(
            sourceAccountId: 'acc-001',
            targetAccountId: 'target-acc',
            threshold: Money::of(5000, 'USD'),
            minimumRetained: Money::of(5000, 'USD')
        );

        $this->assertNotNull($result);
        $this->assertEquals(10000.0, $result->sweepAmount->getAmount());
    }

    public function test_get_accounts_with_excess_cash_returns_sorted_list(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturnMap([
                ['acc-001', 15000.0],
                ['acc-002', 25000.0],
                ['acc-003', 3000.0],
            ]);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $result = $this->service->getAccountsWithExcessCash(
            threshold: Money::of(5000, 'USD'),
            accountIds: ['acc-001', 'acc-002', 'acc-003']
        );

        $this->assertCount(2, $result);
        $this->assertEquals('acc-002', $result[0]['account_id']);
        $this->assertEquals('acc-001', $result[1]['account_id']);
    }

    public function test_calculate_concentration_efficiency_returns_metrics(): void
    {
        $this->cashManagementProvider
            ->method('getCurrentBalance')
            ->willReturnMap([
                ['target-acc', 30000.0],
                ['acc-001', 15000.0],
                ['acc-002', 20000.0],
            ]);

        $this->cashManagementProvider
            ->method('getCurrency')
            ->willReturn('USD');

        $result = $this->service->calculateConcentrationEfficiency(
            targetAccountId: 'target-acc',
            sourceAccountIds: ['acc-001', 'acc-002']
        );

        $this->assertEquals(35000.0, $result['total_balance']);
        $this->assertEquals(30000.0, $result['target_balance']);
        $this->assertArrayHasKey('concentration_percentage', $result);
        $this->assertArrayHasKey('unconcentrated', $result);
    }

    private function createInstruction(): CashSweepInstruction
    {
        return new CashSweepInstruction(
            sourceAccountId: 'acc-001',
            targetAccountId: 'target-acc',
            sweepThreshold: Money::of(5000, 'USD'),
            sweepAmount: Money::of(10000, 'USD'),
            retainMinimum: false,
            retainAmount: null
        );
    }
}
