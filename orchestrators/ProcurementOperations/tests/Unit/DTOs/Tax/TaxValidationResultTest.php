<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationError;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationResult;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationWarning;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaxValidationResult::class)]
final class TaxValidationResultTest extends TestCase
{
    #[Test]
    public function valid_creates_valid_result(): void
    {
        $result = TaxValidationResult::valid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
        );

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->hasErrors());
        $this->assertFalse($result->hasWarnings());
        $this->assertEquals(1000_00, $result->totalNetAmount->getAmountInCents());
        $this->assertEquals(60_00, $result->totalTaxAmount->getAmountInCents());
    }

    #[Test]
    public function invalid_creates_invalid_result(): void
    {
        $errors = [
            TaxValidationError::invalidTaxCode(
                lineNumber: 1,
                taxCode: 'INVALID',
                message: 'Tax code INVALID is not valid',
            ),
        ];

        $result = TaxValidationResult::invalid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
            errors: $errors,
        );

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->errors);
    }

    #[Test]
    public function validWithWarnings_creates_valid_result_with_warnings(): void
    {
        $warnings = [
            TaxValidationWarning::largeVariance(
                lineNumber: 1,
                expectedTax: Money::of(60, 'MYR'),
                actualTax: Money::of(65, 'MYR'),
                variance: 8.33,
            ),
        ];

        $result = TaxValidationResult::validWithWarnings(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(65, 'MYR'),
            totalGrossAmount: Money::of(1065, 'MYR'),
            warnings: $warnings,
        );

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertCount(1, $result->warnings);
    }

    #[Test]
    public function getBlockingErrors_returns_only_blocking_errors(): void
    {
        $errors = [
            TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid tax code'),
            TaxValidationError::calculationMismatch(
                lineNumber: 2,
                expectedTax: Money::of(60, 'MYR'),
                actualTax: Money::of(50, 'MYR'),
                variance: 16.67,
            ),
        ];

        $result = TaxValidationResult::invalid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
            errors: $errors,
        );

        $blockingErrors = $result->getBlockingErrors();

        $this->assertNotEmpty($blockingErrors);
    }

    #[Test]
    public function getTaxSummary_returns_aggregated_data(): void
    {
        $result = TaxValidationResult::valid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
        );

        $summary = $result->getTaxSummary();

        $this->assertArrayHasKey('net_amount', $summary);
        $this->assertArrayHasKey('tax_amount', $summary);
        $this->assertArrayHasKey('gross_amount', $summary);
        $this->assertArrayHasKey('effective_rate', $summary);
    }

    #[Test]
    public function getEffectiveTaxRate_calculates_correctly(): void
    {
        $result = TaxValidationResult::valid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
        );

        $rate = $result->getEffectiveTaxRate();

        // 60 / 1000 * 100 = 6%
        $this->assertEquals(6.0, $rate);
    }

    #[Test]
    public function getErrorsByLine_groups_errors_by_line(): void
    {
        $errors = [
            TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid code'),
            TaxValidationError::rateMismatch(1, 6.0, 5.0, 'Rate mismatch'),
            TaxValidationError::invalidTaxCode(2, 'WRONG', 'Wrong code'),
        ];

        $result = TaxValidationResult::invalid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
            errors: $errors,
        );

        $errorsByLine = $result->getErrorsByLine();

        $this->assertArrayHasKey(1, $errorsByLine);
        $this->assertArrayHasKey(2, $errorsByLine);
        $this->assertCount(2, $errorsByLine[1]);
        $this->assertCount(1, $errorsByLine[2]);
    }

    #[Test]
    public function canProceedWithPayment_returns_false_when_blocking_errors(): void
    {
        $errors = [
            TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid tax code'),
        ];

        $result = TaxValidationResult::invalid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
            errors: $errors,
        );

        $this->assertFalse($result->canProceedWithPayment());
    }

    #[Test]
    public function canProceedWithPayment_returns_true_for_valid(): void
    {
        $result = TaxValidationResult::valid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
        );

        $this->assertTrue($result->canProceedWithPayment());
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $result = TaxValidationResult::valid(
            totalNetAmount: Money::of(1000, 'MYR'),
            totalTaxAmount: Money::of(60, 'MYR'),
            totalGrossAmount: Money::of(1060, 'MYR'),
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('is_valid', $array);
        $this->assertArrayHasKey('total_net_amount', $array);
        $this->assertArrayHasKey('total_tax_amount', $array);
        $this->assertArrayHasKey('total_gross_amount', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertArrayHasKey('warnings', $array);
    }

    #[Test]
    public function invalid_throws_exception_when_only_calculatedTax_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Both $calculatedTax and $statedTax must be provided together to compute variance, or both must be null.');

        TaxValidationResult::invalid(
            invoiceId: 'INV-001',
            errors: [
                TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid tax code'),
            ],
            calculatedTax: Money::of(60, 'MYR'),
            statedTax: null,
        );
    }

    #[Test]
    public function invalid_throws_exception_when_only_statedTax_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Both $calculatedTax and $statedTax must be provided together to compute variance, or both must be null.');

        TaxValidationResult::invalid(
            invoiceId: 'INV-001',
            errors: [
                TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid tax code'),
            ],
            calculatedTax: null,
            statedTax: Money::of(60, 'MYR'),
        );
    }

    #[Test]
    public function invalid_accepts_both_tax_amounts_as_null(): void
    {
        $result = TaxValidationResult::invalid(
            invoiceId: 'INV-001',
            errors: [
                TaxValidationError::invalidTaxCode(1, 'INVALID', 'Invalid tax code'),
            ],
            calculatedTax: null,
            statedTax: null,
        );

        $this->assertFalse($result->isValid);
        $this->assertNull($result->calculatedTax);
        $this->assertNull($result->statedTax);
        $this->assertNull($result->variance);
    }

    #[Test]
    public function invalid_accepts_both_tax_amounts_provided(): void
    {
        $calculatedTax = Money::of(60, 'MYR');
        $statedTax = Money::of(65, 'MYR');

        $result = TaxValidationResult::invalid(
            invoiceId: 'INV-001',
            errors: [
                TaxValidationError::calculationMismatch(
                    lineNumber: 1,
                    expectedTax: $calculatedTax,
                    actualTax: $statedTax,
                    variance: 8.33,
                ),
            ],
            calculatedTax: $calculatedTax,
            statedTax: $statedTax,
        );

        $this->assertFalse($result->isValid);
        $this->assertSame($calculatedTax, $result->calculatedTax);
        $this->assertSame($statedTax, $result->statedTax);
        $this->assertNotNull($result->variance);
        $this->assertEquals(5_00, $result->variance->getAmountInCents());
    }
}
