<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\AchEntryRequest;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\TransactionCode;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class AchEntryRequestTest extends TestCase
{
    public function test_get_transaction_code_returns_explicit_transaction_code_when_provided(): void
    {
        $request = new AchEntryRequest(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '1234567890',
            accountType: AccountType::CHECKING,
            amount: Money::of(1.00, 'USD'),
            receiverName: 'John Doe',
            receiverId: 'ID',
            transactionCode: TransactionCode::CHECKING_DEBIT,
            isDebit: false,
            isPrenote: false,
        );

        self::assertSame(TransactionCode::CHECKING_DEBIT, $request->getTransactionCode());
    }

    public function test_get_transaction_code_returns_prenote_codes_based_on_account_type_and_debit_flag(): void
    {
        $creditChecking = AchEntryRequest::prenote(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '123',
            accountType: AccountType::CHECKING,
            receiverName: 'Jane',
            isDebit: false,
        );

        $debitSavings = AchEntryRequest::prenote(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '456',
            accountType: AccountType::SAVINGS,
            receiverName: 'Jane',
            isDebit: true,
        );

        self::assertSame(TransactionCode::CHECKING_CREDIT_PRENOTE, $creditChecking->getTransactionCode());
        self::assertSame(TransactionCode::SAVINGS_DEBIT_PRENOTE, $debitSavings->getTransactionCode());
    }

    public function test_has_addenda_returns_true_only_when_non_empty(): void
    {
        $withAddenda = AchEntryRequest::credit(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '123',
            accountType: AccountType::CHECKING,
            amount: Money::of(1.23, 'USD'),
            receiverName: 'John Doe',
            receiverId: 'ID',
            addendaRecord: 'ADDENDA',
        );

        $withoutAddenda = AchEntryRequest::credit(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '123',
            accountType: AccountType::CHECKING,
            amount: Money::of(1.23, 'USD'),
            receiverName: 'John Doe',
            receiverId: 'ID',
            addendaRecord: null,
        );

        self::assertTrue($withAddenda->hasAddenda());
        self::assertFalse($withoutAddenda->hasAddenda());
    }

    public function test_validate_returns_errors_for_required_and_length_constraints(): void
    {
        $request = new AchEntryRequest(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '',
            accountType: AccountType::CHECKING,
            amount: Money::zero('USD'),
            receiverName: '',
            receiverId: str_repeat('A', 16),
            transactionCode: null,
            isDebit: false,
            isPrenote: false,
            addendaRecord: str_repeat('B', 81),
        );

        $errors = $request->validate();

        self::assertContains('Account number is required', $errors);
        self::assertContains('Receiver name is required', $errors);
        self::assertContains('Receiver ID must not exceed 15 characters', $errors);
        self::assertContains('Amount must be greater than zero for non-prenote entries', $errors);
        self::assertContains('Addenda record must not exceed 80 characters', $errors);
    }

    public function test_validate_allows_zero_amount_for_prenote_entries(): void
    {
        $request = AchEntryRequest::prenote(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '123',
            accountType: AccountType::CHECKING,
            receiverName: 'Jane',
            isDebit: false,
        );

        self::assertSame([], $request->validate());
    }
}
