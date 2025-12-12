<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\PaymentBatchStatus;
use Nexus\ProcurementOperations\Services\BankFileGenerationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(BankFileGenerationService::class)]
final class BankFileGenerationServiceTest extends TestCase
{
    private BankFileGenerationService $service;

    protected function setUp(): void
    {
        $this->service = new BankFileGenerationService(
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_generates_valid_nacha_file(): void
    {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $this->assertInstanceOf(BankFileGenerationResult::class, $result);
        $this->assertTrue($result->validationPassed);
        $this->assertEquals('NACHA', $result->format);
        $this->assertNotEmpty($result->fileName);
        $this->assertStringEndsWith('.ach', $result->fileName);
        $this->assertEquals($batch->batchId, $result->batchId);
        $this->assertEquals($batch->totalAmount->getAmount(), $result->totalAmount->getAmount());
    }

    #[Test]
    public function it_generates_nacha_file_with_correct_structure(): void
    {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $lines = explode("\n", $result->fileContent);

        // Verify File Header (Record Type 1)
        $this->assertStringStartsWith('1', $lines[0]);

        // Verify Batch Header (Record Type 5)
        $this->assertStringStartsWith('5', $lines[1]);

        // Verify at least one Entry Detail (Record Type 6)
        $hasEntryDetail = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, '6')) {
                $hasEntryDetail = true;
                break;
            }
        }
        $this->assertTrue($hasEntryDetail);

        // Verify Batch Control (Record Type 8)
        $hasBatchControl = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, '8')) {
                $hasBatchControl = true;
                break;
            }
        }
        $this->assertTrue($hasBatchControl);

        // Verify File Control (Record Type 9)
        $hasFileControl = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, '9') && strlen($line) === 94 && !preg_match('/^9+$/', $line)) {
                $hasFileControl = true;
                break;
            }
        }
        $this->assertTrue($hasFileControl);
    }

    #[Test]
    public function it_pads_nacha_file_to_blocking_factor(): void
    {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $lines = explode("\n", $result->fileContent);

        // NACHA blocking factor is 10
        $this->assertEquals(0, count($lines) % 10);
    }

    #[Test]
    public function it_fails_nacha_generation_with_invalid_routing_number(): void
    {
        $batch = $this->createBatchWithInvalidRouting();

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $this->assertFalse($result->validationPassed);
        $this->assertNotEmpty($result->validationErrors);
    }

    #[Test]
    public function it_fails_nacha_generation_with_invalid_company_id(): void
    {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '12345', // Too short - must be 10 chars
        );

        $this->assertFalse($result->validationPassed);
        $this->assertContains('Company ID must be exactly 10 characters', $result->validationErrors);
    }

    #[Test]
    public function it_generates_valid_iso20022_file(): void
    {
        $batch = $this->createTestBatch('WIRE');

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
        );

        $this->assertInstanceOf(BankFileGenerationResult::class, $result);
        $this->assertTrue($result->validationPassed);
        $this->assertEquals('ISO20022', $result->format);
        $this->assertStringEndsWith('.xml', $result->fileName);
        $this->assertEquals('pain.001.001.03', $result->schemaVersion);
    }

    #[Test]
    public function it_generates_iso20022_with_correct_xml_structure(): void
    {
        $batch = $this->createTestBatch('WIRE');

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
        );

        $xml = $result->fileContent;

        // Verify XML is well-formed
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml));

        // Verify required elements
        $this->assertStringContainsString('<Document', $xml);
        $this->assertStringContainsString('<CstmrCdtTrfInitn>', $xml);
        $this->assertStringContainsString('<GrpHdr>', $xml);
        $this->assertStringContainsString('<MsgId>', $xml);
        $this->assertStringContainsString('<CreDtTm>', $xml);
        $this->assertStringContainsString('<NbOfTxs>', $xml);
        $this->assertStringContainsString('<CtrlSum>', $xml);
        $this->assertStringContainsString('<InitgPty>', $xml);
        $this->assertStringContainsString('<PmtInf>', $xml);
        $this->assertStringContainsString('<Dbtr>', $xml);
        $this->assertStringContainsString('<DbtrAcct>', $xml);
        $this->assertStringContainsString('<CdtTrfTxInf>', $xml);
    }

    #[Test]
    public function it_includes_correct_transaction_count_in_iso20022(): void
    {
        $batch = $this->createBatchWithMultipleItems();

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
        );

        $this->assertEquals($batch->itemCount, $result->numberOfTransactions);

        // Verify NbOfTxs in XML matches
        $this->assertStringContainsString(
            '<NbOfTxs>' . $batch->itemCount . '</NbOfTxs>',
            $result->fileContent
        );
    }

    #[Test]
    public function it_fails_iso20022_generation_with_unsupported_schema(): void
    {
        $batch = $this->createTestBatch('WIRE');

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
            schemaVersion: 'pain.001.001.99', // Invalid schema
        );

        $this->assertFalse($result->validationPassed);
        $this->assertNotEmpty($result->validationErrors);
    }

    #[Test]
    public function it_validates_generated_nacha_file(): void
    {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $validation = $this->service->validateGeneratedFile($result);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertEquals('NACHA', $validation['format']);
    }

    #[Test]
    public function it_validates_generated_iso20022_file(): void
    {
        $batch = $this->createTestBatch('WIRE');

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
        );

        $validation = $this->service->validateGeneratedFile($result);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertEquals('ISO20022', $validation['format']);
    }

    #[Test]
    public function it_generates_positive_pay_file_for_checks(): void
    {
        $batch = $this->createTestBatch('CHECK');

        $content = $this->service->generatePositivePayFile(
            batch: $batch,
            accountNumber: '1234567890',
            format: 'STANDARD',
        );

        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Account Number', $content); // Header
        $this->assertStringContainsString('1234567890', $content);

        $lines = explode("\n", $content);
        $this->assertGreaterThanOrEqual(2, count($lines)); // Header + at least 1 item
    }

    #[Test]
    public function it_fails_positive_pay_for_non_check_payments(): void
    {
        $batch = $this->createTestBatch('ACH');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Positive Pay files can only be generated for check payments');

        $this->service->generatePositivePayFile(
            batch: $batch,
            accountNumber: '1234567890',
        );
    }

    #[Test]
    public function it_generates_bai2_format_positive_pay(): void
    {
        $batch = $this->createTestBatch('CHECK');

        $content = $this->service->generatePositivePayFile(
            batch: $batch,
            accountNumber: '1234567890',
            format: 'BAI2',
        );

        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('01,', $content); // BAI2 header
        $this->assertStringContainsString('16,475,', $content); // Transaction record
        $this->assertStringContainsString('99,', $content); // Trailer
    }

    #[Test]
    #[DataProvider('secCodeProvider')]
    public function it_generates_nacha_with_different_sec_codes(
        string $secCode,
        bool $shouldPass,
    ): void {
        $batch = $this->createTestBatch('ACH');

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
            secCode: $secCode,
        );

        $this->assertEquals($shouldPass, $result->validationPassed);

        if ($shouldPass) {
            $this->assertStringContainsString($secCode, $result->fileContent);
        }
    }

    public static function secCodeProvider(): array
    {
        return [
            'CCD - Corporate Credit/Debit' => ['CCD', true],
            'CTX - Corporate Trade Exchange' => ['CTX', true],
            'PPD - Prearranged Payment' => ['PPD', true],
            'WEB - Internet Initiated' => ['WEB', true],
            'TEL - Telephone Initiated' => ['TEL', true],
            'INVALID - Invalid SEC Code' => ['INVALID', false],
        ];
    }

    #[Test]
    public function it_sets_correct_file_creation_date(): void
    {
        $batch = $this->createTestBatch('ACH');
        $beforeGeneration = new \DateTimeImmutable();

        $result = $this->service->generateNachaFile(
            batch: $batch,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $afterGeneration = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual(
            $beforeGeneration->getTimestamp(),
            $result->fileCreationDate->getTimestamp()
        );
        $this->assertLessThanOrEqual(
            $afterGeneration->getTimestamp(),
            $result->fileCreationDate->getTimestamp()
        );
    }

    #[Test]
    public function it_calculates_control_sum_correctly_in_iso20022(): void
    {
        $batch = $this->createBatchWithMultipleItems();

        $result = $this->service->generateIso20022File(
            batch: $batch,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'DE89370400440532013000',
            debtorBankBic: 'COBADEFFXXX',
        );

        $this->assertEquals(
            $batch->totalAmount->getAmount(),
            $result->controlSum
        );

        // Verify CtrlSum in XML
        $expectedSum = number_format($batch->totalAmount->getAmount(), 2, '.', '');
        $this->assertStringContainsString(
            '<CtrlSum>' . $expectedSum . '</CtrlSum>',
            $result->fileContent
        );
    }

    /**
     * Create a test batch with a single item.
     */
    private function createTestBatch(string $paymentMethod): PaymentBatchData
    {
        $item = new PaymentItemData(
            itemId: 'ITEM-001',
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor Inc',
            paymentAmount: Money::of(5000.00, 'USD'),
            invoiceNumber: 'INV-2024-001',
            routingNumber: '123456789',
            bankAccountNumber: '9876543210',
            beneficiaryAccountNumber: 'DE89370400440532013001',
            beneficiaryBankSwift: 'COBADEFFXXX',
            checkNumber: $paymentMethod === 'CHECK' ? 'CHK-001' : null,
            payeeName: 'Test Vendor Inc',
        );

        return new PaymentBatchData(
            batchId: 'BATCH-001',
            batchName: 'Test Batch',
            paymentMethod: $paymentMethod,
            totalAmount: Money::of(5000.00, 'USD'),
            itemCount: 1,
            status: PaymentBatchStatus::APPROVED,
            createdBy: 'USER-001',
            createdAt: new \DateTimeImmutable(),
            scheduledPaymentDate: new \DateTimeImmutable('+3 days'),
            items: [$item],
        );
    }

    /**
     * Create a batch with invalid routing number.
     */
    private function createBatchWithInvalidRouting(): PaymentBatchData
    {
        $item = new PaymentItemData(
            itemId: 'ITEM-001',
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor Inc',
            paymentAmount: Money::of(5000.00, 'USD'),
            invoiceNumber: 'INV-2024-001',
            routingNumber: '1234', // Invalid - too short
            bankAccountNumber: '9876543210',
        );

        return new PaymentBatchData(
            batchId: 'BATCH-001',
            batchName: 'Invalid Batch',
            paymentMethod: 'ACH',
            totalAmount: Money::of(5000.00, 'USD'),
            itemCount: 1,
            status: PaymentBatchStatus::APPROVED,
            createdBy: 'USER-001',
            createdAt: new \DateTimeImmutable(),
            items: [$item],
        );
    }

    /**
     * Create a batch with multiple items.
     */
    private function createBatchWithMultipleItems(): PaymentBatchData
    {
        $items = [];
        $total = 0.0;

        for ($i = 1; $i <= 3; $i++) {
            $amount = $i * 1000.00;
            $total += $amount;

            $items[] = new PaymentItemData(
                itemId: "ITEM-00{$i}",
                invoiceId: "INV-00{$i}",
                vendorId: "VENDOR-00{$i}",
                vendorName: "Vendor {$i} Inc",
                paymentAmount: Money::of($amount, 'USD'),
                invoiceNumber: "INV-2024-00{$i}",
                routingNumber: str_repeat((string) $i, 9),
                bankAccountNumber: str_repeat((string) $i, 10),
                beneficiaryAccountNumber: "DE8937040044053201300{$i}",
                beneficiaryBankSwift: 'COBADEFFXXX',
            );
        }

        return new PaymentBatchData(
            batchId: 'BATCH-MULTI',
            batchName: 'Multi-Item Batch',
            paymentMethod: 'WIRE',
            totalAmount: Money::of($total, 'USD'),
            itemCount: count($items),
            status: PaymentBatchStatus::APPROVED,
            createdBy: 'USER-001',
            createdAt: new \DateTimeImmutable(),
            items: $items,
        );
    }
}
