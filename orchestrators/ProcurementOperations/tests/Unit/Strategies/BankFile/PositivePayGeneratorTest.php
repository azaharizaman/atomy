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
            format: PositivePayFormat::STANDARD_CSV,
            bankAccountNumber: '123456789012',
            bankRoutingNumber: 'BANK001',
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
        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            paymentItems: [],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
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
        $payment = new PaymentItemData(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            checkNumber: '', // Missing check number - required for positive pay
            paymentReference: 'INV-001',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            paymentItems: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('PAY-001', $errors);
        $this->assertStringContainsString('check number', $errors['PAY-001'][0]);
    }

    #[Test]
    public function it_generates_standard_csv_format(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getContent());
        $this->assertSame(BankFileFormat::POSITIVE_PAY, $result->getFormat());

        // CSV should have header and data rows
        $lines = explode("\n", trim($result->getContent()));
        $this->assertGreaterThanOrEqual(2, count($lines));
    }

    #[Test]
    public function it_generates_csv_with_correct_header(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

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
            format: $format,
            bankAccountNumber: '123456789012',
            bankRoutingNumber: 'BANK001',
        );

        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getContent());
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
        $lines = explode("\n", trim($result->getContent()));

        // Bank of America uses fixed-width format, all lines same length
        $lineLength = strlen($lines[0]);
        foreach ($lines as $line) {
            $this->assertSame($lineLength, strlen($line), 'All lines should be same length');
        }
    }

    #[Test]
    public function it_generates_wells_fargo_format_with_header_trailer(): void
    {
        $config = PositivePayConfiguration::wellsFargo('123456789012', '121000248', 'ACME Corp', 'ACME001');
        $generator = new PositivePayGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

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
        $content = $result->getContent();

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
        $lines = explode("\n", trim($result->getContent()));

        // Citi uses record type prefix
        $this->assertStringStartsWith('01', $lines[0], 'First record should have header type 01');
    }

    #[Test]
    public function it_categorizes_issued_checks(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('issued_checks_count', $array);
        $this->assertGreaterThan(0, $array['issued_checks_count']);
    }

    #[Test]
    public function it_categorizes_voided_checks(): void
    {
        $voidedPayment = new PaymentItemData(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(0.00, 'USD'), // Voided check has zero amount
            checkNumber: '1001',
            paymentReference: 'VOID',
            checkType: 'VOID',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            paymentItems: [$voidedPayment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('voided_checks_count', $array);
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
        $filename = $result->getSuggestedFilename();

        $this->assertStringStartsWith('POSITIVEPAY_', $filename);
        $this->assertStringContainsString('BATCH-001', $filename);
    }

    #[Test]
    public function it_handles_special_characters_in_payee_names(): void
    {
        $payment = new PaymentItemData(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: "O'Brien & Associates, Inc.",
            amount: Money::of(1000.00, 'USD'),
            checkNumber: '1001',
            paymentReference: 'INV-001',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            paymentItems: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

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
        return new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            paymentItems: [
                $this->createCheckPayment('PAY-001', '1001', 1000.00),
                $this->createCheckPayment('PAY-002', '1002', 2500.00),
                $this->createCheckPayment('PAY-003', '1003', 750.50),
            ],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );
    }

    private function createCheckPayment(string $id, string $checkNumber, float $amount): PaymentItemData
    {
        return new PaymentItemData(
            paymentItemId: $id,
            vendorId: 'VENDOR-' . substr($id, -3),
            vendorName: 'Test Vendor ' . substr($id, -3),
            amount: Money::of($amount, 'USD'),
            checkNumber: $checkNumber,
            checkDate: new \DateTimeImmutable(),
            paymentReference: 'INV-' . substr($id, -3),
        );
    }
}
