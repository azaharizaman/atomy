<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\DTOs\AchEntryRequest;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class AchBatchRequestTest extends TestCase
{
    public function test_get_entry_count_returns_number_of_entries(): void
    {
        $batch = new AchBatchRequest(
            companyName: 'ACME INC',
            companyId: 'ACME',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: new RoutingNumber('021000021'),
            secCode: SecCode::PPD,
            entries: [
                AchEntryRequest::credit(
                    receivingDfi: new RoutingNumber('021000021'),
                    accountNumber: '123',
                    accountType: AccountType::CHECKING,
                    amount: Money::of(1.00, 'USD'),
                    receiverName: 'John Doe',
                ),
                AchEntryRequest::debit(
                    receivingDfi: new RoutingNumber('021000021'),
                    accountNumber: '456',
                    accountType: AccountType::CHECKING,
                    amount: Money::of(2.00, 'USD'),
                    receiverName: 'Jane Doe',
                ),
            ],
        );

        self::assertSame(2, $batch->getEntryCount());
    }

    public function test_get_total_debits_and_credits_sums_entries(): void
    {
        $batch = new AchBatchRequest(
            companyName: 'ACME INC',
            companyId: 'ACME',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: new RoutingNumber('021000021'),
            secCode: SecCode::PPD,
            entries: [
                AchEntryRequest::credit(
                    receivingDfi: new RoutingNumber('021000021'),
                    accountNumber: '123',
                    accountType: AccountType::CHECKING,
                    amount: Money::of(1.50, 'USD'),
                    receiverName: 'John Doe',
                ),
                AchEntryRequest::debit(
                    receivingDfi: new RoutingNumber('021000021'),
                    accountNumber: '456',
                    accountType: AccountType::CHECKING,
                    amount: Money::of(2.25, 'USD'),
                    receiverName: 'Jane Doe',
                ),
            ],
        );

        self::assertSame(150, $batch->getTotalCredits()->getAmountInMinorUnits());
        self::assertSame(225, $batch->getTotalDebits()->getAmountInMinorUnits());
    }

    public function test_validate_includes_entry_validation_errors_with_index_prefix(): void
    {
        $invalidEntry = new AchEntryRequest(
            receivingDfi: new RoutingNumber('021000021'),
            accountNumber: '',
            accountType: AccountType::CHECKING,
            amount: Money::of(1.00, 'USD'),
            receiverName: '',
            receiverId: '',
        );

        $batch = new AchBatchRequest(
            companyName: str_repeat('A', 17),
            companyId: str_repeat('B', 11),
            companyEntryDescription: str_repeat('C', 11),
            originatingDfi: new RoutingNumber('021000021'),
            secCode: SecCode::PPD,
            entries: [$invalidEntry],
        );

        $errors = $batch->validate();

        self::assertContains('Company name must not exceed 16 characters', $errors);
        self::assertContains('Company ID must not exceed 10 characters', $errors);
        self::assertContains('Entry description must not exceed 10 characters', $errors);
        self::assertContains('Entry 0: Account number is required', $errors);
        self::assertContains('Entry 0: Receiver name is required', $errors);
    }

    public function test_validate_requires_at_least_one_entry(): void
    {
        $batch = new AchBatchRequest(
            companyName: 'ACME INC',
            companyId: 'ACME',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: new RoutingNumber('021000021'),
            secCode: SecCode::PPD,
            entries: [],
        );

        self::assertContains('Batch must contain at least one entry', $batch->validate());
    }
}
