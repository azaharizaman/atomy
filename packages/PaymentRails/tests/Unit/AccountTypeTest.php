<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\TransactionCode;
use PHPUnit\Framework\TestCase;

final class AccountTypeTest extends TestCase
{
    public function test_label_returns_human_readable_value(): void
    {
        self::assertSame('Checking', AccountType::CHECKING->label());
        self::assertSame('Savings', AccountType::SAVINGS->label());
        self::assertSame('General Ledger', AccountType::GL->label());
        self::assertSame('Loan', AccountType::LOAN->label());
    }

    public function test_supportsAch_is_true_only_for_checking_and_savings(): void
    {
        self::assertTrue(AccountType::CHECKING->supportsAch());
        self::assertTrue(AccountType::SAVINGS->supportsAch());

        self::assertFalse(AccountType::GL->supportsAch());
        self::assertFalse(AccountType::LOAN->supportsAch());
    }

    public function test_ach_codes_map_for_checking_and_savings_only(): void
    {
        self::assertSame(TransactionCode::CHECKING_CREDIT, AccountType::CHECKING->achCreditCode());
        self::assertSame(TransactionCode::CHECKING_DEBIT, AccountType::CHECKING->achDebitCode());

        self::assertSame(TransactionCode::SAVINGS_CREDIT, AccountType::SAVINGS->achCreditCode());
        self::assertSame(TransactionCode::SAVINGS_DEBIT, AccountType::SAVINGS->achDebitCode());

        self::assertNull(AccountType::GL->achCreditCode());
        self::assertNull(AccountType::GL->achDebitCode());
        self::assertNull(AccountType::LOAN->achCreditCode());
        self::assertNull(AccountType::LOAN->achDebitCode());
    }
}
