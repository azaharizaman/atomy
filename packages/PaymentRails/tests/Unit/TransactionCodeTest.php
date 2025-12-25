<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\TransactionCode;
use PHPUnit\Framework\TestCase;

final class TransactionCodeTest extends TestCase
{
    public function test_code_casts_to_int(): void
    {
        self::assertSame(22, TransactionCode::CHECKING_CREDIT->code());
        self::assertSame(39, TransactionCode::SAVINGS_DEBIT_ZERO->code());
    }

    public function test_direction_and_account_type_helpers(): void
    {
        self::assertTrue(TransactionCode::CHECKING_CREDIT->isCredit());
        self::assertFalse(TransactionCode::CHECKING_CREDIT->isDebit());

        self::assertTrue(TransactionCode::SAVINGS_DEBIT->isDebit());
        self::assertFalse(TransactionCode::SAVINGS_DEBIT->isCredit());

        self::assertTrue(TransactionCode::CHECKING_DEBIT_ZERO->isChecking());
        self::assertFalse(TransactionCode::CHECKING_DEBIT_ZERO->isSavings());

        self::assertTrue(TransactionCode::SAVINGS_CREDIT_PRENOTE->isSavings());
        self::assertFalse(TransactionCode::SAVINGS_CREDIT_PRENOTE->isChecking());
    }

    public function test_prenote_and_zero_dollar_helpers(): void
    {
        self::assertTrue(TransactionCode::CHECKING_CREDIT_PRENOTE->isPrenote());
        self::assertFalse(TransactionCode::CHECKING_CREDIT->isPrenote());

        self::assertTrue(TransactionCode::CHECKING_DEBIT_ZERO->isZeroDollar());
        self::assertFalse(TransactionCode::SAVINGS_DEBIT->isZeroDollar());
    }

    public function test_toPrenote_maps_all_variants_to_their_prenote_code(): void
    {
        self::assertSame(
            TransactionCode::CHECKING_CREDIT_PRENOTE,
            TransactionCode::CHECKING_CREDIT->toPrenote()
        );

        self::assertSame(
            TransactionCode::CHECKING_CREDIT_PRENOTE,
            TransactionCode::CHECKING_CREDIT_ZERO->toPrenote()
        );

        self::assertSame(
            TransactionCode::CHECKING_DEBIT_PRENOTE,
            TransactionCode::CHECKING_DEBIT->toPrenote()
        );

        self::assertSame(
            TransactionCode::SAVINGS_CREDIT_PRENOTE,
            TransactionCode::SAVINGS_CREDIT->toPrenote()
        );

        self::assertSame(
            TransactionCode::SAVINGS_DEBIT_PRENOTE,
            TransactionCode::SAVINGS_DEBIT_ZERO->toPrenote()
        );
    }

    public function test_factory_methods_return_expected_codes(): void
    {
        self::assertSame(TransactionCode::CHECKING_CREDIT, TransactionCode::creditToChecking());
        self::assertSame(TransactionCode::CHECKING_DEBIT, TransactionCode::debitFromChecking());
        self::assertSame(TransactionCode::SAVINGS_CREDIT, TransactionCode::creditToSavings());
        self::assertSame(TransactionCode::SAVINGS_DEBIT, TransactionCode::debitFromSavings());
    }
}
