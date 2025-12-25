<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class AchEntryTest extends TestCase
{
    public function test_credit_sets_credit_transaction_code_and_addenda_indicator(): void
    {
        $entry = AchEntry::credit(
            id: 'e1',
            routingNumber: new RoutingNumber('021000021'),
            accountNumber: '12345678901234567890',
            accountType: AccountType::CHECKING,
            amount: Money::of(12.34, 'USD'),
            individualName: 'John Doe',
            individualId: 'ID-123',
            addenda: 'PAYROLL'
        );

        self::assertTrue($entry->isCredit());
        self::assertFalse($entry->isDebit());
        self::assertFalse($entry->isPrenote());
        self::assertTrue($entry->hasAddenda());
        self::assertSame(1, $entry->getAddendaIndicator());

        self::assertSame(1234, $entry->getAmountInCents());
    }

    public function test_debit_sets_debit_transaction_code_and_formats_fields(): void
    {
        $entry = AchEntry::debit(
            id: 'e2',
            routingNumber: new RoutingNumber('222371863'),
            accountNumber: '123456789012345678',
            accountType: AccountType::SAVINGS,
            amount: Money::of('0.01', 'USD'),
            individualName: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            individualId: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            addenda: null
        );

        self::assertTrue($entry->isDebit());
        self::assertFalse($entry->isCredit());
        self::assertFalse($entry->hasAddenda());
        self::assertSame(0, $entry->getAddendaIndicator());

        self::assertSame(17, strlen($entry->getFormattedAccountNumber()));
        self::assertSame('12345678901234567', $entry->getFormattedAccountNumber());

        self::assertSame(22, strlen($entry->getFormattedIndividualName()));
        self::assertSame('ABCDEFGHIJKLMNOPQRSTUV', rtrim($entry->getFormattedIndividualName()));

        self::assertSame(15, strlen($entry->getFormattedIndividualId()));
        self::assertSame('ABCDEFGHIJKLMNO', rtrim($entry->getFormattedIndividualId()));
    }

    public function test_prenote_amount_is_zero_and_can_assign_trace_number(): void
    {
        $entry = AchEntry::prenote(
            id: 'e3',
            routingNumber: new RoutingNumber('021000021'),
            accountNumber: '123',
            accountType: AccountType::CHECKING,
            individualName: 'Jane',
            individualId: 'ID',
            isCredit: true
        );

        self::assertTrue($entry->isPrenote());
        self::assertSame(0, $entry->getAmountInCents());

        $withTrace = $entry->withTraceNumber('123456789012345');
        self::assertSame('123456789012345', $withTrace->traceNumber);
        self::assertNull($entry->traceNumber);
    }

    public function test_transaction_codes_for_all_account_types_and_directions(): void
    {
        $routing = new RoutingNumber('021000021');
        $amount = Money::of(1.00, 'USD');

        // Credit Savings
        $creditSavings = AchEntry::credit('c1', $routing, '1', AccountType::SAVINGS, $amount, 'A', 'A');
        self::assertSame(\Nexus\PaymentRails\Enums\TransactionCode::SAVINGS_CREDIT, $creditSavings->transactionCode);

        // Debit Checking
        $debitChecking = AchEntry::debit('d1', $routing, '1', AccountType::CHECKING, $amount, 'A', 'A');
        self::assertSame(\Nexus\PaymentRails\Enums\TransactionCode::CHECKING_DEBIT, $debitChecking->transactionCode);

        // Prenote Savings Credit
        $prenoteSavingsCredit = AchEntry::prenote('p1', $routing, '1', AccountType::SAVINGS, 'A', 'A', true);
        self::assertSame(\Nexus\PaymentRails\Enums\TransactionCode::SAVINGS_CREDIT_PRENOTE, $prenoteSavingsCredit->transactionCode);

        // Prenote Savings Debit
        $prenoteSavingsDebit = AchEntry::prenote('p2', $routing, '1', AccountType::SAVINGS, 'A', 'A', false);
        self::assertSame(\Nexus\PaymentRails\Enums\TransactionCode::SAVINGS_DEBIT_PRENOTE, $prenoteSavingsDebit->transactionCode);

        // Prenote Checking Debit
        $prenoteCheckingDebit = AchEntry::prenote('p3', $routing, '1', AccountType::CHECKING, 'A', 'A', false);
        self::assertSame(\Nexus\PaymentRails\Enums\TransactionCode::CHECKING_DEBIT_PRENOTE, $prenoteCheckingDebit->transactionCode);
    }
}
