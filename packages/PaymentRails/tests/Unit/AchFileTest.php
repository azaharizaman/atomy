<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class AchFileTest extends TestCase
{
    public function test_create_sets_defaults_and_formats_header_fields(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-02 03:04:05');

        $file = AchFile::create(
            id: 'f1',
            immediateDestination: new RoutingNumber('021000021'),
            immediateOrigin: new RoutingNumber('222371863'),
            immediateDestinationName: 'DEST BANK NAME THAT IS WAY TOO LONG',
            immediateOriginName: 'ORIGIN COMPANY NAME THAT IS WAY TOO LONG',
            fileCreationDateTime: $createdAt
        );

        self::assertSame(0, $file->getBatchCount());
        self::assertFalse($file->hasBatches());
        self::assertSame('A', $file->fileIdModifier);
        self::assertSame(FileStatus::GENERATED, $file->status);

        self::assertSame(' 021000021', $file->getFormattedImmediateDestination());
        self::assertSame(' 222371863', $file->getFormattedImmediateOrigin());

        self::assertSame(23, strlen($file->getFormattedDestinationName()));
        self::assertSame('DEST BANK NAME THAT IS', rtrim($file->getFormattedDestinationName()));

        self::assertSame(23, strlen($file->getFormattedOriginName()));
        self::assertSame('ORIGIN COMPANY NAME THA', rtrim($file->getFormattedOriginName()));

        self::assertSame('250102', $file->getFileCreationDate());
        self::assertSame('0304', $file->getFileCreationTime());

        self::assertSame('ACH_20250102_030405_A.txt', $file->getSuggestedFilename());
    }

    public function test_addBatch_numbers_batches_and_updates_counts_totals_and_records(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-02 03:04:05');

        $file = AchFile::create(
            id: 'f1',
            immediateDestination: new RoutingNumber('021000021'),
            immediateOrigin: new RoutingNumber('222371863'),
            immediateDestinationName: 'DEST',
            immediateOriginName: 'ORIGIN',
            fileCreationDateTime: $createdAt
        );

        $originatingDfi = new RoutingNumber('021000021');
        $receiverA = new RoutingNumber('222371863');
        $receiverB = new RoutingNumber('021000021');

        $batch1 = AchBatch::create(
            id: 'b1',
            secCode: SecCode::PPD,
            companyName: 'COMPANY',
            companyId: '1234567890',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [
                AchEntry::credit('e1', $receiverA, '1', AccountType::CHECKING, Money::of(10.00, 'USD'), 'A', 'A', 'ADDENDA'),
                AchEntry::debit('e2', $receiverB, '2', AccountType::SAVINGS, Money::of(2.50, 'USD'), 'B', 'B', null),
            ]
        );

        $batch2 = AchBatch::create(
            id: 'b2',
            secCode: SecCode::PPD,
            companyName: 'COMPANY2',
            companyId: '1234567890',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: $originatingDfi,
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [
                AchEntry::debit('e3', $receiverA, '3', AccountType::CHECKING, Money::of(1.00, 'USD'), 'C', 'C', null),
            ]
        );

        $file2 = $file->addBatch($batch1);
        self::assertSame(1, $file2->getBatchCount());
        self::assertSame(1, $file2->batches[0]->batchNumber);

        $file3 = $file2->addBatch($batch2);
        self::assertSame(2, $file3->getBatchCount());
        self::assertSame(2, $file3->batches[1]->batchNumber);

        self::assertSame(3, $file3->getEntryCount());
        self::assertSame(1, $file3->getAddendaCount());

        // Record count = 2 (file header/control)
        // + per batch: 2 (header/control) + entries + addenda
        // Batch1: 2 + 2 entries + 1 addenda = 5
        // Batch2: 2 + 1 entry + 0 addenda = 3
        // Total: 2 + 5 + 3 = 10
        self::assertSame(10, $file3->getRecordCount());
        self::assertSame(1, $file3->getBlockCount()); // 10 records => 1 block

        // Totals across batches
        self::assertSame(3.50, $file3->getTotalDebits()->getAmount());
        self::assertSame(10.00, $file3->getTotalCredits()->getAmount());

        $expectedHash = (
            $batch1->getEntryHash() +
            $batch2->getEntryHash()
        ) % 10000000000;

        self::assertSame($expectedHash, $file3->getEntryHash());
    }

    public function test_withStatus_returns_new_instance(): void
    {
        $file = AchFile::create(
            id: 'f1',
            immediateDestination: new RoutingNumber('021000021'),
            immediateOrigin: new RoutingNumber('222371863'),
            immediateDestinationName: 'DEST',
            immediateOriginName: 'ORIGIN',
            fileCreationDateTime: new \DateTimeImmutable('2025-01-02 03:04:05')
        );

        $updated = $file->withStatus(FileStatus::TRANSMITTED);

        self::assertSame(FileStatus::GENERATED, $file->status);
        self::assertSame(FileStatus::TRANSMITTED, $updated->status);
    }
}
