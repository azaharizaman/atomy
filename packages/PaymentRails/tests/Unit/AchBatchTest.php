<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class AchBatchTest extends TestCase
{
    public function test_counts_totals_hash_and_service_class_code(): void
    {
        $originatingDfi = new RoutingNumber('021000021');
        $receiverA = new RoutingNumber('222371863');
        $receiverB = new RoutingNumber('021000021');

        $credit = AchEntry::credit(
            id: 'e1',
            routingNumber: $receiverA,
            accountNumber: '111',
            accountType: AccountType::CHECKING,
            amount: Money::of(10.00, 'USD'),
            individualName: 'Alice',
            individualId: 'A1',
            addenda: 'ADDENDA'
        );

        $debit = AchEntry::debit(
            id: 'e2',
            routingNumber: $receiverB,
            accountNumber: '222',
            accountType: AccountType::SAVINGS,
            amount: Money::of(10.00, 'USD'),
            individualName: 'Bob',
            individualId: 'B1',
            addenda: null
        );

        $batch = AchBatch::create(
            id: 'b1',
            secCode: SecCode::PPD,
            companyName: 'COMPANY NAME THAT IS TOO LONG',
            companyId: '123456789012345',
            companyEntryDescription: 'DESCRIPTION TOO LONG',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [$credit, $debit]
        );

        self::assertSame(2, $batch->getEntryCount());
        self::assertSame(1, $batch->getAddendaCount());

        self::assertSame(10.00, $batch->getTotalDebits()->getAmount());
        self::assertSame(10.00, $batch->getTotalCredits()->getAmount());
        self::assertTrue($batch->isBalanced());
        self::assertTrue($batch->hasEntries());

        // Mixed debits and credits => 200
        self::assertSame(200, $batch->getServiceClassCode());

        $expectedHash = ((int) substr($receiverA->value, 0, 8) + (int) substr($receiverB->value, 0, 8)) % 10000000000;
        self::assertSame($expectedHash, $batch->getEntryHash());

        self::assertSame(16, strlen($batch->getFormattedCompanyName()));
        self::assertSame('COMPANY NAME THA', rtrim($batch->getFormattedCompanyName()));

        self::assertSame(10, strlen($batch->getFormattedCompanyId()));
        self::assertSame('1234567890', rtrim($batch->getFormattedCompanyId()));

        self::assertSame(10, strlen($batch->getFormattedEntryDescription()));
        self::assertSame('DESCRIPTIO', rtrim($batch->getFormattedEntryDescription()));
    }

    public function test_service_class_code_for_credits_only_and_debits_only(): void
    {
        $originatingDfi = new RoutingNumber('021000021');
        $receiver = new RoutingNumber('222371863');

        $creditOnly = AchBatch::create(
            id: 'b-credit',
            secCode: SecCode::PPD,
            companyName: 'C',
            companyId: '123',
            companyEntryDescription: 'D',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [
                AchEntry::credit('e1', $receiver, '1', AccountType::CHECKING, Money::of(1.00, 'USD'), 'A', 'A', null),
            ]
        );

        self::assertSame(220, $creditOnly->getServiceClassCode());

        $debitOnly = AchBatch::create(
            id: 'b-debit',
            secCode: SecCode::PPD,
            companyName: 'C',
            companyId: '123',
            companyEntryDescription: 'D',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [
                AchEntry::debit('e2', $receiver, '1', AccountType::CHECKING, Money::of(1.00, 'USD'), 'A', 'A', null),
            ]
        );

        self::assertSame(225, $debitOnly->getServiceClassCode());
    }

    public function test_withBatchNumber_returns_new_instance_with_updated_number(): void
    {
        $batch = AchBatch::create(
            id: 'b1',
            secCode: SecCode::PPD,
            companyName: 'C',
            companyId: '123',
            companyEntryDescription: 'D',
            originatingDfi: new RoutingNumber('021000021'),
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: []
        );

        $updated = $batch->withBatchNumber(7);

        self::assertSame(1, $batch->batchNumber);
        self::assertSame(7, $updated->batchNumber);
    }

    public function test_addEntry_and_addEntries_returns_new_instance_with_added_entries(): void
    {
        $originatingDfi = new RoutingNumber('021000021');
        $receiver = new RoutingNumber('222371863');

        $batch = AchBatch::create(
            id: 'b1',
            secCode: SecCode::PPD,
            companyName: 'C',
            companyId: '123',
            companyEntryDescription: 'D',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: []
        );

        $entry1 = AchEntry::credit('e1', $receiver, '1', AccountType::CHECKING, Money::of(1.00, 'USD'), 'A', 'A');
        $entry2 = AchEntry::debit('e2', $receiver, '2', AccountType::SAVINGS, Money::of(2.00, 'USD'), 'B', 'B');

        $batchWithOne = $batch->addEntry($entry1);
        self::assertCount(0, $batch->entries);
        self::assertCount(1, $batchWithOne->entries);
        self::assertSame($entry1, $batchWithOne->entries[0]);

        $batchWithTwo = $batchWithOne->addEntries([$entry2]);
        self::assertCount(1, $batchWithOne->entries);
        self::assertCount(2, $batchWithTwo->entries);
        self::assertSame($entry2, $batchWithTwo->entries[1]);
    }
}
