<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Enums\TransactionCode;
use Nexus\PaymentRails\Services\NachaFormatter;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use PHPUnit\Framework\TestCase;

final class NachaFormatterTest extends TestCase
{
    public function test_generate_file_outputs_94_char_records(): void
    {
        $formatter = new NachaFormatter();

        $file = $this->createSimpleFile();

        $content = $formatter->generateFile($file);
        $lines = explode("\n", $content);

        self::assertNotEmpty($lines);

        foreach ($lines as $line) {
            self::assertSame(94, strlen($line));
        }
    }

    public function test_generate_file_pads_to_10_record_blocks_with_9s(): void
    {
        $formatter = new NachaFormatter();

        $file = $this->createSimpleFile();

        $content = $formatter->generateFile($file);
        $lines = explode("\n", $content);

        self::assertGreaterThanOrEqual(10, count($lines));
        self::assertSame(0, count($lines) % 10);

        // Last records should be the all-9 blocking records (unless exact multiple already).
        $blocking = str_repeat('9', 94);
        $tail = array_slice($lines, -3);
        self::assertContains($blocking, $tail);
    }

    public function test_generate_file_record_order_is_header_batches_controls_then_file_control(): void
    {
        $formatter = new NachaFormatter();

        $file = $this->createSimpleFile();

        $content = $formatter->generateFile($file);
        $lines = explode("\n", $content);

        self::assertSame('1', $lines[0][0]);

        // Find file control (type 9) - should appear before any blocking records.
        $fileControlIndex = null;
        foreach ($lines as $i => $line) {
            if ($line[0] === '9' && $line !== str_repeat('9', 94)) {
                $fileControlIndex = $i;
                break;
            }
        }

        self::assertNotNull($fileControlIndex);

        // There should be at least one batch header and one batch control before file control.
        $hasBatchHeader = false;
        $hasBatchControl = false;
        for ($i = 0; $i < $fileControlIndex; $i++) {
            $hasBatchHeader = $hasBatchHeader || $lines[$i][0] === '5';
            $hasBatchControl = $hasBatchControl || $lines[$i][0] === '8';
        }

        self::assertTrue($hasBatchHeader);
        self::assertTrue($hasBatchControl);

        // After file control, everything should be blocking records.
        $blocking = str_repeat('9', 94);
        for ($i = $fileControlIndex + 1; $i < count($lines); $i++) {
            self::assertSame($blocking, $lines[$i]);
        }
    }

    public function test_parse_file_round_trips_basic_header_and_counts(): void
    {
        $formatter = new NachaFormatter();

        $file = $this->createSimpleFile();
        $content = $formatter->generateFile($file);

        $parsed = $formatter->parseFile($content);

        self::assertSame($file->immediateDestination->value, $parsed->immediateDestination->value);
        self::assertSame($file->immediateOrigin->value, $parsed->immediateOrigin->value);
        self::assertSame($file->fileIdModifier, $parsed->fileIdModifier);
        self::assertSame($file->getBatchCount(), $parsed->getBatchCount());
        self::assertSame($file->getEntryCount(), $parsed->getEntryCount());
    }

    private function createSimpleFile(): AchFile
    {
        $entry = new AchEntry(
            id: 'entry-1',
            transactionCode: TransactionCode::CHECKING_CREDIT,
            routingNumber: new RoutingNumber('011000015'),
            accountNumber: '123456789',
            accountType: AccountType::CHECKING,
            amount: new Money(1234, 'USD'),
            individualName: 'John Doe',
            individualId: 'INV-1000',
            discretionaryData: null,
            addenda: 'Hello',
            traceNumber: null
        );

        $batch = new AchBatch(
            id: 'batch-1',
            secCode: SecCode::PPD,
            companyName: 'Example Co',
            companyId: '123456789',
            companyEntryDescription: 'PAYROLL',
            originatingDfi: new RoutingNumber('011000015'),
            effectiveEntryDate: new \DateTimeImmutable('2025-01-02'),
            entries: [$entry],
            companyDiscretionaryData: null,
            companyDescriptiveDate: new \DateTimeImmutable('2025-01-01'),
            batchNumber: 1
        );

        return new AchFile(
            id: 'file-1',
            immediateDestination: new RoutingNumber('011000015'),
            immediateOrigin: new RoutingNumber('011000015'),
            immediateDestinationName: 'DEST BANK',
            immediateOriginName: 'ORIGIN BANK',
            fileCreationDateTime: new \DateTimeImmutable('2025-01-02 03:04:00'),
            batches: [$batch],
            fileIdModifier: 'A',
            status: FileStatus::GENERATED,
            referenceCode: null
        );
    }
}
