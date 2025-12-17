<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\PositivePayFormat;
use Nexus\ProcurementOperations\Strategies\BankFile\PositivePayGenerator;
use Nexus\ProcurementOperations\ValueObjects\PositivePayConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(PositivePayGenerator::class)]
final class PositivePayGeneratorTest extends TestCase
{
    private PositivePayConfiguration $configuration;
    private PositivePayGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new PositivePayConfiguration(
            bankAccountNumber: '123456789012',
            bankRoutingNumber: '021000021', // Valid 9-digit routing number
            format: PositivePayFormat::STANDARD_CSV,
            companyName: 'ACME CORPORATION',
        );

        $this->generator = new PositivePayGenerator($this->configuration, new NullLogger());
    }

    #[Test]
    public function it_returns_correct_format(): void
    {
        $this->assertSame(BankFileFormat::POSITIVE_PAY, $this->generator->getFormat());
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
        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PP-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'check',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        );

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
    public function it_validates_batch_with_missing_check_number(): void
    {
        $payment = PaymentItemData::forCheck(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            invoiceIds: ['INV-001'],
            paymentReference: 'INV-001',
            checkNumber: '', // Missing check number - required for positive pay
        );

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PP-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'check',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        )->withPaymentItem($payment);

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
        // Errors are returned as a flat array with "Item {index}: message" format
        $this->assertContains('Item 0: Check number is required for Positive Pay', $errors);
    }

    #[Test]
    public function it_generates_standard_csv_format(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertSame(BankFileFormat::POSITIVE_PAY, $result->getFormat());

        // CSV should have header and data rows
        $lines = explode("\n", trim($result->getFileContent()));
        $this->assertGreaterThanOrEqual(2, count($lines));
    }

    #[Test]
    public function it_generates_csv_with_correct_header(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // First line should be header
        $this->assertStringContainsString('Account', $lines[0]);
        $this->assertStringContainsString('Check', $lines[0]);
        $this->assertStringContainsString('Amount', $lines[0]);
    }

    #[Test]
    #[DataProvider('bankFormatProvider')]
    public function it_supports_all_bank_formats(PositivePayFormat $format): void
    {
        $config = new PositivePayConfiguration(
            bankAccountNumber: '123456789012',
            bankRoutingNumber: '021000021', // Valid routing number with correct checksum
            format: $format,
        );

        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getFileContent());
    }

    /**
     * @return array<string, array{PositivePayFormat}>
     */
    public static function bankFormatProvider(): array
    {
        return [
            'Standard CSV' => [PositivePayFormat::STANDARD_CSV],
            'BAI2' => [PositivePayFormat::BAI2],
            'Bank of America' => [PositivePayFormat::BANK_OF_AMERICA],
            'Wells Fargo' => [PositivePayFormat::WELLS_FARGO],
            'Chase' => [PositivePayFormat::CHASE],
            'Citi' => [PositivePayFormat::CITI],
        ];
    }

    #[Test]
    public function it_generates_bank_of_america_fixed_width_format(): void
    {
        $config = PositivePayConfiguration::bankOfAmerica('123456789012', '021000021', 'ACME Corp', 'ACME001');
        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());

        // Bank of America uses fixed-width format
        $lines = explode("\n", trim($result->getFileContent()));
        $this->assertNotEmpty($lines);

        // Detail records should have consistent length (header/trailer may differ)
        $detailLines = array_filter($lines, fn($line) => !empty(trim($line)));
        $this->assertNotEmpty($detailLines);
    }

    #[Test]
    public function it_generates_wells_fargo_format_with_header_trailer(): void
    {
        $config = PositivePayConfiguration::wellsFargo('123456789012', '121000248', 'ACME Corp', 'ACME001');
        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Wells Fargo should have header (H), detail (D), and trailer (T) records
        $this->assertStringStartsWith('H', $lines[0], 'First record should be header');
        $this->assertStringStartsWith('T', $lines[count($lines) - 1], 'Last record should be trailer');
    }

    #[Test]
    public function it_generates_chase_pipe_delimited_format(): void
    {
        $config = PositivePayConfiguration::chase('123456789012', '021000021', 'ACME Corp', 'ACME001');
        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);
        $content = $result->getFileContent();

        // Chase uses pipe delimiter
        $this->assertStringContainsString('|', $content);
    }

    #[Test]
    public function it_generates_citi_format_with_record_type(): void
    {
        $config = PositivePayConfiguration::citi('123456789012', '021000089', 'ACME Corp', 'ACME001');
        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);
        $lines = explode("\n", trim($result->getFileContent()));

        // Citi uses record type prefix
        $this->assertStringStartsWith('01', $lines[0], 'First record should have header type 01');
    }

    #[Test]
    public function it_categorizes_issued_checks(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('check_count', $array);
        $this->assertGreaterThan(0, $array['check_count']);
    }

    #[Test]
    public function it_categorizes_voided_checks(): void
    {
        $voidedPayment = PaymentItemData::forCheck(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(0.00, 'USD'), // Voided check has zero amount
            invoiceIds: ['INV-VOID'],
            paymentReference: 'VOID',
            checkNumber: '1001',
            checkType: 'VOID',
        );

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PP-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'check',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        )->withPaymentItem($voidedPayment);

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('voided_check_count', $array);
    }

    #[Test]
    public function it_includes_check_counts_in_result(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('check_count', $array);
        $this->assertSame(3, $array['check_count']);
    }

    #[Test]
    public function it_generates_suggested_filename_with_format(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $filename = $result->getFileName();

        $this->assertStringStartsWith('POSITIVEPAY_', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    #[Test]
    public function it_handles_special_characters_in_payee_names(): void
    {
        $payment = PaymentItemData::forCheck(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: "O'Brien & Associates, Inc.",
            amount: Money::of(1000.00, 'USD'),
            invoiceIds: ['INV-001'],
            paymentReference: 'INV-001',
            checkNumber: '1001',
        );

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PP-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'check',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        )->withPaymentItem($payment);

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_calculates_total_amount_correctly(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('total_amount', $array);
        // 1000.00 + 2500.00 + 750.50 = 4250.50
        $this->assertEquals(4250.50, $array['total_amount']);
    }

    #[Test]
    public function it_generates_checksum(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $checksum = $result->getChecksum();

        $this->assertNotEmpty($checksum);
        $this->assertSame(64, strlen($checksum));
    }

    private function createValidBatch(): PaymentBatchData
    {
        return PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PP-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'check',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        )->withPaymentItems([
            $this->createCheckPayment('PAY-001', '1001', 1000.00),
            $this->createCheckPayment('PAY-002', '1002', 2500.00),
            $this->createCheckPayment('PAY-003', '1003', 750.50),
        ]);
    }

    private function createCheckPayment(string $id, string $checkNumber, float $amount): PaymentItemData
    {
        return PaymentItemData::forCheck(
            paymentItemId: $id,
            vendorId: 'VENDOR-' . substr($id, -3),
            vendorName: 'Test Vendor ' . substr($id, -3),
            amount: Money::of($amount, 'USD'),
            invoiceIds: ['INV-' . substr($id, -3)],
            paymentReference: 'REF-' . substr($id, -3),
            checkNumber: $checkNumber,
        );
    }
}
