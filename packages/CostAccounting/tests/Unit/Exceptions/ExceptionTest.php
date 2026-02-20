<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\Exceptions;

use Nexus\CostAccounting\Exceptions\AllocationCycleDetectedException;
use Nexus\CostAccounting\Exceptions\CostAccountingException;
use Nexus\CostAccounting\Exceptions\CostCenterNotFoundException;
use Nexus\CostAccounting\Exceptions\CostPoolNotFoundException;
use Nexus\CostAccounting\Exceptions\InsufficientCostPoolException;
use Nexus\CostAccounting\Exceptions\InvalidAllocationRuleException;
use Nexus\CostAccounting\Exceptions\ProductCostNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Exception classes
 */
final class ExceptionTest extends TestCase
{
    public function testCostAccountingException(): void
    {
        $exception = new CostAccountingException('Test message');
        
        self::assertSame('Test message', $exception->getMessage());
        self::assertInstanceOf(\Exception::class, $exception);
    }

    public function testCostPoolNotFoundException(): void
    {
        $poolId = 'pool_123';
        $exception = new CostPoolNotFoundException($poolId);
        
        self::assertSame("Cost pool not found: {$poolId}", $exception->getMessage());
        self::assertSame($poolId, $exception->getPoolId());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testCostCenterNotFoundException(): void
    {
        $costCenterId = 'cc_123';
        $exception = new CostCenterNotFoundException($costCenterId);
        
        self::assertSame("Cost center not found: {$costCenterId}", $exception->getMessage());
        self::assertSame($costCenterId, $exception->getCostCenterId());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testInsufficientCostPoolException(): void
    {
        $poolId = 'pool_123';
        $available = 500.00;
        $requested = 1000.00;
        $exception = new InsufficientCostPoolException($poolId, $available, $requested);
        
        self::assertStringContainsString($poolId, $exception->getMessage());
        self::assertStringContainsString('500.00', $exception->getMessage());
        self::assertStringContainsString('1000.00', $exception->getMessage());
        self::assertSame($poolId, $exception->getPoolId());
        self::assertSame($available, $exception->getAvailable());
        self::assertSame($requested, $exception->getRequested());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testProductCostNotFoundException(): void
    {
        $productId = 'product_1';
        $periodId = 'period_1';
        $exception = new ProductCostNotFoundException($productId, $periodId);
        
        self::assertStringContainsString($productId, $exception->getMessage());
        self::assertStringContainsString($periodId, $exception->getMessage());
        self::assertSame($productId, $exception->getProductId());
        self::assertSame($periodId, $exception->getPeriodId());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testInvalidAllocationRuleException(): void
    {
        $poolId = 'pool_123';
        $message = 'Invalid allocation rule';
        $exception = new InvalidAllocationRuleException($poolId, $message);
        
        self::assertStringContainsString($poolId, $exception->getMessage());
        self::assertStringContainsString($message, $exception->getMessage());
        self::assertSame($poolId, $exception->getRuleId());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testAllocationCycleDetectedException(): void
    {
        $cycle = ['pool_1', 'cc_1', 'pool_2'];
        $exception = new AllocationCycleDetectedException($cycle);
        
        self::assertStringContainsString('Circular allocation dependency detected', $exception->getMessage());
        self::assertSame($cycle, $exception->getCyclePath());
        self::assertInstanceOf(CostAccountingException::class, $exception);
    }

    public function testExceptionsAreThrowables(): void
    {
        $exceptions = [
            new CostAccountingException('test'),
            new CostPoolNotFoundException('pool_1'),
            new CostCenterNotFoundException('cc_1'),
            new InsufficientCostPoolException('pool_1', 100.0, 200.0),
            new ProductCostNotFoundException('product_1', 'period_1'),
            new InvalidAllocationRuleException('pool_1', 'test'),
            new AllocationCycleDetectedException(['a', 'b']),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(\Throwable::class, $exception);
        }
    }
}
