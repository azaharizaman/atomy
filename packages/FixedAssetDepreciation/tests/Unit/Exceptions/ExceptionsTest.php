<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Exceptions;

use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationException;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testDepreciationExceptionWithContext(): void
    {
        $context = ['asset_id' => 'asset_001', 'period' => '2024-01'];
        
        $exception = DepreciationException::withContext(
            'Test error message',
            $context
        );

        self::assertStringContainsString('Test error message', $exception->getMessage());
        self::assertStringContainsString('asset_001', $exception->getMessage());
        self::assertStringContainsString('2024-01', $exception->getMessage());
        self::assertSame('DEPRECIATION_ERROR', $exception->getErrorCode());
    }

    public function testDepreciationExceptionWithContextEmptyArray(): void
    {
        $exception = DepreciationException::withContext(
            'Simple error'
        );

        self::assertSame('Simple error', $exception->getMessage());
        self::assertFalse(str_contains($exception->getMessage(), 'Context:'));
    }

    public function testDepreciationExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        
        $exception = DepreciationException::withContext(
            'New error',
            [],
            $previous
        );

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testDepreciationCalculationExceptionConstructor(): void
    {
        $exception = new DepreciationCalculationException(
            assetId: 'asset_001',
            message: 'Test calculation error',
            context: ['field' => 'cost', 'value' => -100]
        );

        self::assertStringContainsString('asset_001', $exception->getMessage());
        self::assertStringContainsString('Test calculation error', $exception->getMessage());
        self::assertStringContainsString('cost', $exception->getMessage());
        self::assertStringContainsString('-100', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionInvalidCost(): void
    {
        $exception = DepreciationCalculationException::invalidCost('asset_002', -500.0);

        self::assertStringContainsString('asset_002', $exception->getMessage());
        self::assertStringContainsString('Invalid asset cost', $exception->getMessage());
        self::assertStringContainsString('-500', $exception->getMessage());
        // The error code is inherited from parent
        self::assertSame('DEPRECIATION_ERROR', $exception->getErrorCode());
    }

    public function testDepreciationCalculationExceptionInvalidUsefulLife(): void
    {
        $exception = DepreciationCalculationException::invalidUsefulLife('asset_003', 0);

        self::assertStringContainsString('asset_003', $exception->getMessage());
        self::assertStringContainsString('Invalid useful life', $exception->getMessage());
        self::assertStringContainsString('0', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionSalvageExceedsCost(): void
    {
        $exception = DepreciationCalculationException::salvageExceedsCost('asset_004', 15000.0, 10000.0);

        self::assertStringContainsString('asset_004', $exception->getMessage());
        self::assertStringContainsString('Salvage value cannot exceed cost', $exception->getMessage());
        self::assertStringContainsString('15000', $exception->getMessage());
        self::assertStringContainsString('10000', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionAlreadyFullyDepreciated(): void
    {
        $exception = DepreciationCalculationException::alreadyFullyDepreciated('asset_005', 0.0);

        self::assertStringContainsString('asset_005', $exception->getMessage());
        self::assertStringContainsString('Asset is already fully depreciated', $exception->getMessage());
        self::assertStringContainsString('0', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionMethodNotSupported(): void
    {
        $exception = DepreciationCalculationException::methodNotSupported('asset_006', 'InvalidMethod');

        self::assertStringContainsString('asset_006', $exception->getMessage());
        self::assertStringContainsString('Depreciation method not supported', $exception->getMessage());
        self::assertStringContainsString('InvalidMethod', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionInvalidPeriod(): void
    {
        $exception = DepreciationCalculationException::invalidPeriod('asset_007', 'invalid-period-id');

        self::assertStringContainsString('asset_007', $exception->getMessage());
        self::assertStringContainsString('Invalid period', $exception->getMessage());
        self::assertStringContainsString('invalid-period-id', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionMissingRequiredData(): void
    {
        $exception = DepreciationCalculationException::missingRequiredData('asset_008', 'cost');

        self::assertStringContainsString('asset_008', $exception->getMessage());
        self::assertStringContainsString('Missing required data', $exception->getMessage());
        self::assertStringContainsString('cost', $exception->getMessage());
    }

    public function testDepreciationCalculationExceptionExtendsDepreciationException(): void
    {
        $exception = DepreciationCalculationException::invalidCost('asset_009', -100.0);

        self::assertInstanceOf(DepreciationException::class, $exception);
    }

    public function testDepreciationCalculationExceptionWithPrevious(): void
    {
        $previous = new \InvalidArgumentException('Original error');
        
        $exception = new DepreciationCalculationException(
            assetId: 'asset_010',
            message: 'Wrapped error',
            previous: $previous
        );

        self::assertSame($previous, $exception->getPrevious());
    }
}
