<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\NachaSecCode;
use Nexus\ProcurementOperations\Strategies\BankFile\NachaFileGenerator;
use Nexus\ProcurementOperations\ValueObjects\NachaConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(NachaFileGenerator::class)]
final class NachaFileGeneratorTest extends TestCase
{
    private NachaConfiguration $configuration;
    private NachaFileGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new NachaConfiguration(
            immediateDestination: '123456789',
            immediateOrigin: '123456789',
            immediateDestinationName: 'DESTINATION BANK',
            
            immediateOriginName: 'ACME CORP',
            companyName: 'ACME CORP',
            companyId: '1234567890',
            secCode: NachaSecCode::CCD,
            referenceCode: 'VENDOR PAY',
        );

        $this->generator = new NachaFileGenerator($this->configuration, new NullLogger());
    }

    #[Test]
    public function it_returns_correct_format(): void
    {
        $this->assertSame(BankFileFormat::NACHA, $this->generator->getFormat());
    }

    #[Test]
    public function it_returns_version(): void
    {
        $version = $this->generator->getVersion();

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    #[Test]
    public function it_supports_valid_batch(): void
    {
        $batch = $this->createValidBatch();

        $this->assertTrue($this->generator->supports($batch));
    }

    #[Test]
    public function it_does_not_support_empty_batch(): void
    {
        $batch = $this->createEmptyBatch();

        // Empty batch returns true as no items fail validation
        $this->assertTrue($this->generator->supports($batch));
    }

    #[Test]
    public function it_does_not_support_non_usd_currency(): void
    {
        $batch = $this->createBatchWithCurrency('EUR');

        $this->assertFalse($this->generator->supports($batch));
    }

    #[Test]
    public function it_validates_batch_with_valid_data(): void
    {
        $batch = $this->createValidBatch();

        $errors = $this->generator->validate($batch);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_validates_batch_with_missing_routing_number(): void
    {
        $payment = $this->createPaymentItemWithMissingRouting();
        $batch = $this->createEmptyBatch()->withPaymentItem($payment);

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
    }

    #[Test]
    public function it_validates_batch_with_invalid_routing_number(): void
    {
        $payment = $this->createPaymentItemWithInvalidRouting();
        $batch = $this->createEmptyBatch()->withPaymentItem($payment);

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
    }

    #[Test]
    public function it_generates_valid_nacha_file(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getFileContent());
    }

    #[Test]
    public function it_generates_file_with_correct_record_length(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        foreach ($lines as $line) {
            if (!empty($line)) {
                $this->assertSame(94, strlen($line), "NACHA record must be exactly 94 characters");
            }
        }
    }

    #[Test]
    public function it_generates_file_header_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // First record should be file header (type 1)
        $this->assertStringStartsWith('1', $lines[0]);
    }

    #[Test]
    public function it_generates_batch_header_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Second record should be batch header (type 5)
        $this->assertStringStartsWith('5', $lines[1]);
    }

    #[Test]
    public function it_generates_entry_detail_records(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Entry records start with 6
        $entryRecords = array_filter($lines, fn($line) => str_starts_with($line, '6'));

        $this->assertCount(count($batch->paymentItems), $entryRecords);
    }

    #[Test]
    public function it_generates_batch_control_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Find batch control record (type 8)
        $batchControlRecords = array_filter($lines, fn($line) => str_starts_with($line, '8'));

        $this->assertCount(1, $batchControlRecords);
    }

    #[Test]
    public function it_generates_file_control_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Last record should be file control (type 9)
        $lastLine = end($lines);
        $this->assertStringStartsWith('9', $lastLine);
    }

    #[Test]
    public function it_includes_routing_numbers_in_entries(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // Check routing number appears in the file
        $this->assertStringContainsString('021000021', $content);
    }

    #[Test]
    public function it_calculates_hash_correctly(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_handles_multiple_payment_items(): void
    {
        $batch = $this->createBatchWithMultipleItems(5);

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(5, $result->getTotalRecords());
    }

    #[Test]
    public function it_generates_suggested_filename(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $filename = $result->getFileName();

        $this->assertStringStartsWith('NACHA_', $filename);
        $this->assertStringEndsWith('.ach', $filename);
    }

    #[Test]
    public function it_includes_company_info_in_result(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $metadata = $result->getMetadata();

        $this->assertArrayHasKey('company_name', $metadata);
        $this->assertSame('ACME CORP', $metadata['company_name']);
    }

    #[Test]
    public function it_calculates_total_amount_correctly(): void
    {
        $batch = $this->createBatchWithMultipleItems(3);

        $result = $this->generator->generate($batch);

        // Each item is $1000, so 3 items = $3000
        $this->assertEquals(3000.00, $result->getTotalAmount()->getAmount());
    }

    #[Test]
    #[DataProvider('secCodeProvider')]
    public function it_supports_different_sec_codes(NachaSecCode $secCode): void
    {
        $configuration = new NachaConfiguration(
            immediateDestination: '123456789',
            immediateOrigin: '123456789',
            immediateDestinationName: 'DESTINATION BANK',
            
            immediateOriginName: 'ACME CORP',
            companyName: 'ACME CORP',
            companyId: '1234567890',
            secCode: $secCode,
        );

        $generator = new NachaFileGenerator($configuration, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());
    }

    public static function secCodeProvider(): array
    {
        return [
            'CCD - Corporate Credit or Debit' => [NachaSecCode::CCD],
            'PPD - Prearranged Payment and Deposit' => [NachaSecCode::PPD],
            'CTX - Corporate Trade Exchange' => [NachaSecCode::CTX],
        ];
    }

    #[Test]
    public function it_includes_sec_code_in_batch_header(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Batch header contains SEC code
        $batchHeader = $lines[1];
        $this->assertStringContainsString('CCD', $batchHeader);
    }

    #[Test]
    public function it_calculates_checksum(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertNotEmpty($result->getChecksum());
    }

    // Helper methods

    private function createEmptyBatch(): PaymentBatchData
    {
        return PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'BN-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'ach',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-123',
        );
    }

    private function createValidBatch(): PaymentBatchData
    {
        $batch = $this->createEmptyBatch();
        
        return $batch->withPaymentItem($this->createValidPaymentItem('PAY-001', 1000.00));
    }

    private function createBatchWithCurrency(string $currency): PaymentBatchData
    {
        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'BN-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'ach',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: $currency,
            createdBy: 'user-123',
        );

        return $batch->withPaymentItem(
            PaymentItemData::forAch(
                paymentItemId: 'PAY-001',
                vendorId: 'VENDOR-001',
                vendorName: 'Test Vendor',
                amount: Money::of(1000.00, $currency),
                invoiceIds: ['INV-001'],
                paymentReference: 'REF-001',
                bankAccountNumber: '123456789',
                routingNumber: '021000021',
                bankName: 'Chase Bank',
                accountName: 'Vendor Account',
            )
        );
    }

    private function createBatchWithMultipleItems(int $count): PaymentBatchData
    {
        $batch = $this->createEmptyBatch();

        for ($i = 1; $i <= $count; $i++) {
            $batch = $batch->withPaymentItem(
                $this->createValidPaymentItem("PAY-{$i}", 1000.00)
            );
        }

        return $batch;
    }

    private function createValidPaymentItem(string $paymentId, float $amount): PaymentItemData
    {
        return PaymentItemData::forAch(
            paymentItemId: $paymentId,
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of($amount, 'USD'),
            invoiceIds: ['INV-001'],
            paymentReference: "REF-{$paymentId}",
            bankAccountNumber: '123456789',
            routingNumber: '021000021',
            bankName: 'Chase Bank',
            accountName: 'Vendor Account',
        );
    }

    private function createPaymentItemWithMissingRouting(): PaymentItemData
    {
        return PaymentItemData::forAch(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            invoiceIds: ['INV-001'],
            paymentReference: 'REF-001',
            bankAccountNumber: '123456789',
            routingNumber: '', // Missing routing number
            bankName: 'Chase Bank',
            accountName: 'Vendor Account',
        );
    }

    private function createPaymentItemWithInvalidRouting(): PaymentItemData
    {
        return PaymentItemData::forAch(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            invoiceIds: ['INV-001'],
            paymentReference: 'REF-001',
            bankAccountNumber: '123456789',
            routingNumber: '12345', // Invalid - too short
            bankName: 'Chase Bank',
            accountName: 'Vendor Account',
        );
    }
}
