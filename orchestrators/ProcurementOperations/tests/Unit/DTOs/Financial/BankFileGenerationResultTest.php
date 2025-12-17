<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BankFileGenerationResult::class)]
final class BankFileGenerationResultTest extends TestCase
{
    #[Test]
    public function it_creates_nacha_file_result(): void
    {
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA file content here...',
            totalAmount: Money::of(50000.00, 'USD'),
            recordCount: 10,
            batchCount: 2,
        );

        $this->assertTrue($result->success);
        $this->assertSame('payment_20240120.ach', $result->fileName);
        $this->assertSame('NACHA', $result->fileFormat);
        $this->assertSame('NACHA file content here...', $result->fileContent);
        $this->assertSame(50000.0, $result->totalAmount->getAmount());
        $this->assertSame(10, $result->recordCount);
        $this->assertSame(2, $result->batchCount);
        $this->assertNotNull($result->checksum);
    }

    #[Test]
    public function it_creates_iso20022_file_result(): void
    {
        $xmlContent = '<?xml version="1.0"?><pain.001>...</pain.001>';
        
        $result = BankFileGenerationResult::iso20022File(
            fileName: 'payment_20240120.xml',
            fileContent: $xmlContent,
            totalAmount: Money::of(100000.00, 'USD'),
            recordCount: 5,
            messageId: 'MSG-2024-001',
        );

        $this->assertTrue($result->success);
        $this->assertSame('payment_20240120.xml', $result->fileName);
        $this->assertSame('ISO20022', $result->fileFormat);
        $this->assertSame('MSG-2024-001', $result->messageId);
    }

    #[Test]
    public function it_creates_check_print_file_result(): void
    {
        $result = BankFileGenerationResult::checkPrintFile(
            fileName: 'checks_20240120.pdf',
            fileContent: 'PDF content...',
            totalAmount: Money::of(25000.00, 'USD'),
            checkCount: 15,
            startingCheckNumber: '10001',
            endingCheckNumber: '10015',
        );

        $this->assertTrue($result->success);
        $this->assertSame('checks_20240120.pdf', $result->fileName);
        $this->assertSame('CHECK_PRINT', $result->fileFormat);
        $this->assertSame(15, $result->recordCount);
        $this->assertSame('10001', $result->startingCheckNumber);
        $this->assertSame('10015', $result->endingCheckNumber);
    }

    #[Test]
    public function it_creates_failure_result(): void
    {
        $result = BankFileGenerationResult::failure(
            errorCode: 'INVALID_FORMAT',
            errorMessage: 'Bank account format invalid for ACH processing',
            failedPayments: [
                'pmt-001' => ['reason' => 'Invalid routing number'],
                'pmt-003' => ['reason' => 'Account number too short'],
            ],
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->fileName);
        $this->assertSame('INVALID_FORMAT', $result->errorCode);
        $this->assertSame('Bank account format invalid for ACH processing', $result->errorMessage);
        $this->assertCount(2, $result->failedPayments);
    }

    #[Test]
    public function it_tracks_successful_and_failed_payments(): void
    {
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(45000.00, 'USD'),
            recordCount: 9,
            batchCount: 2,
            successfulPayments: ['pmt-001', 'pmt-002', 'pmt-003', 'pmt-004', 'pmt-005', 'pmt-006', 'pmt-007', 'pmt-008', 'pmt-009'],
            failedPayments: ['pmt-010' => ['reason' => 'Invalid account']],
        );

        $this->assertCount(9, $result->successfulPayments);
        $this->assertCount(1, $result->failedPayments);
        $this->assertSame(9, $result->getSuccessCount());
        $this->assertSame(1, $result->getFailureCount());
        $this->assertSame(90.0, $result->getSuccessRate());
    }

    #[Test]
    public function it_calculates_checksum(): void
    {
        $result1 = BankFileGenerationResult::nachaFile(
            fileName: 'payment_1.ach',
            fileContent: 'Content A',
            totalAmount: Money::of(10000.00, 'USD'),
            recordCount: 5,
            batchCount: 1,
        );

        $result2 = BankFileGenerationResult::nachaFile(
            fileName: 'payment_2.ach',
            fileContent: 'Content B',
            totalAmount: Money::of(10000.00, 'USD'),
            recordCount: 5,
            batchCount: 1,
        );

        // Different content should have different checksums
        $this->assertNotSame($result1->checksum, $result2->checksum);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(50000.00, 'USD'),
            recordCount: 10,
            batchCount: 2,
        );

        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('payment_20240120.ach', $array['file_name']);
        $this->assertSame('NACHA', $array['file_format']);
        $this->assertSame(10, $array['record_count']);
        $this->assertSame(2, $array['batch_count']);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('checksum', $array);
        $this->assertArrayHasKey('generated_at', $array);
    }

    #[Test]
    public function it_provides_file_size(): void
    {
        $content = str_repeat('A', 1024); // 1KB of content
        
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: $content,
            totalAmount: Money::of(50000.00, 'USD'),
            recordCount: 10,
            batchCount: 2,
        );

        $this->assertSame(1024, $result->getFileSizeBytes());
    }

    #[Test]
    public function it_handles_partial_success(): void
    {
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(40000.00, 'USD'),
            recordCount: 8,
            batchCount: 2,
            successfulPayments: ['pmt-001', 'pmt-002', 'pmt-003', 'pmt-004', 'pmt-005', 'pmt-006', 'pmt-007', 'pmt-008'],
            failedPayments: [
                'pmt-009' => ['reason' => 'Invalid routing number'],
                'pmt-010' => ['reason' => 'Account closed'],
            ],
        );

        $this->assertTrue($result->success);
        $this->assertTrue($result->hasPartialFailures());
        $this->assertSame(8, $result->getSuccessCount());
        $this->assertSame(2, $result->getFailureCount());
        $this->assertSame(80.0, $result->getSuccessRate());
    }

    #[Test]
    public function it_excludes_file_content_from_array_when_requested(): void
    {
        $result = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content that should not be exposed...',
            totalAmount: Money::of(50000.00, 'USD'),
            recordCount: 10,
            batchCount: 2,
        );

        $arrayWithContent = $result->toArray(includeContent: true);
        $arrayWithoutContent = $result->toArray(includeContent: false);

        $this->assertArrayHasKey('file_content', $arrayWithContent);
        $this->assertArrayNotHasKey('file_content', $arrayWithoutContent);
    }
}
