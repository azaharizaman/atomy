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
            immediateOrigin: '123456789',
            immediateDestination: '987654321',
            companyName: 'ACME CORP',
            companyId: '1234567890',
            secCode: NachaSecCode::CCD,
            discretionaryData: 'VENDOR PAY',
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
        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($this->generator->supports($batch));
    }

    #[Test]
    public function it_does_not_support_non_usd_currency(): void
    {
        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [$this->createPaymentItem('PAY-001', 1000.00)],
            currency: 'EUR',
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
    public function it_validates_batch_with_missing_routing_number(): void
    {
        $payment = new PaymentItemData(
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            routingNumber: '', // Missing routing number
            accountNumber: '123456789',
            paymentReference: 'INV-001',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('PAY-001', $errors);
        $this->assertStringContainsString('routing number', $errors['PAY-001'][0]);
    }

    #[Test]
    public function it_validates_batch_with_invalid_routing_number(): void
    {
        $payment = new PaymentItemData(
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'USD'),
            routingNumber: '12345', // Too short
            accountNumber: '123456789',
            paymentReference: 'INV-001',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
    }

    #[Test]
    public function it_generates_valid_nacha_file(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getContent());
        $this->assertSame(BankFileFormat::NACHA, $result->getFormat());
    }

    #[Test]
    public function it_generates_file_with_correct_record_length(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

        foreach ($lines as $line) {
            // Each NACHA record should be exactly 94 characters
            $this->assertSame(94, strlen($line), "Line should be 94 characters: '{$line}'");
        }
    }

    #[Test]
    public function it_generates_file_with_blocking_factor(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

        // NACHA files should have a record count divisible by 10 (blocking factor)
        $this->assertSame(0, count($lines) % 10, 'Record count should be divisible by 10');
    }

    #[Test]
    public function it_calculates_correct_entry_hash(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        // Entry hash should be present in result
        $this->assertArrayHasKey('entry_hash', $result->toArray());
        $this->assertNotEmpty($result->toArray()['entry_hash']);
    }

    #[Test]
    public function it_generates_correct_file_header_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

        // First record should be file header (type 1)
        $this->assertStringStartsWith('1', $lines[0]);
        $this->assertStringContainsString('987654321', $lines[0]); // Immediate destination
        $this->assertStringContainsString('123456789', $lines[0]); // Immediate origin
    }

    #[Test]
    public function it_generates_correct_batch_header_record(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

        // Second record should be batch header (type 5)
        $this->assertStringStartsWith('5', $lines[1]);
        $this->assertStringContainsString('ACME CORP', $lines[1]);
    }

    #[Test]
    public function it_generates_correct_entry_detail_records(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $lines = explode("\n", trim($result->getContent()));

        // Entry detail records should be type 6
        $entryRecords = array_filter($lines, fn($line) => str_starts_with($line, '6'));

        $this->assertNotEmpty($entryRecords);
    }

    #[Test]
    public function it_includes_control_totals_in_result(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('total_debit_amount', $array);
        $this->assertArrayHasKey('total_credit_amount', $array);
        $this->assertArrayHasKey('entry_count', $array);
    }

    #[Test]
    public function it_generates_suggested_filename(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $filename = $result->getSuggestedFilename();

        $this->assertStringStartsWith('NACHA_BATCH-001_', $filename);
        $this->assertStringEndsWith('.ach', $filename);
    }

    #[Test]
    public function it_calculates_checksum(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $checksum = $result->getChecksum();

        $this->assertNotEmpty($checksum);
        $this->assertSame(64, strlen($checksum)); // SHA-256 produces 64 hex chars
    }

    #[Test]
    #[DataProvider('secCodeProvider')]
    public function it_supports_all_sec_codes(NachaSecCode $secCode): void
    {
        $config = new NachaConfiguration(
            immediateOrigin: '123456789',
            immediateDestination: '987654321',
            companyName: 'TEST COMPANY',
            secCode: $secCode,
        );

        $generator = new NachaFileGenerator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());
    }

    /**
     * @return array<string, array{NachaSecCode}>
     */
    public static function secCodeProvider(): array
    {
        return [
            'CCD - Corporate Credit/Debit' => [NachaSecCode::CCD],
            'CTX - Corporate Trade Exchange' => [NachaSecCode::CTX],
            'PPD - Prearranged Payment' => [NachaSecCode::PPD],
            'WEB - Internet-Initiated' => [NachaSecCode::WEB],
        ];
    }

    #[Test]
    public function it_handles_large_amounts_correctly(): void
    {
        $payment = new PaymentItemData(
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Big Vendor',
            amount: Money::of(9999999.99, 'USD'),
            routingNumber: '123456789',
            accountNumber: '1234567890',
            paymentReference: 'LARGE-PAY',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_handles_special_characters_in_names(): void
    {
        $payment = new PaymentItemData(
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Müller & Söhne GmbH', // Special characters
            amount: Money::of(1000.00, 'USD'),
            routingNumber: '123456789',
            accountNumber: '1234567890',
            paymentReference: 'INV-001',
        );

        $batch = new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [$payment],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );

        $result = $this->generator->generate($batch);

        // Should succeed - special characters should be sanitized
        $this->assertTrue($result->isSuccess());
        // Content should only contain alphanumeric and allowed special chars
        $content = $result->getContent();
        $this->assertDoesNotMatchRegularExpression('/[äöüß]/i', $content);
    }

    private function createValidBatch(): PaymentBatchData
    {
        return new PaymentBatchData(
            batchId: 'BATCH-001',
            tenantId: 'tenant-123',
            payments: [
                $this->createPaymentItem('PAY-001', 1000.00),
                $this->createPaymentItem('PAY-002', 2500.00),
                $this->createPaymentItem('PAY-003', 750.50),
            ],
            currency: 'USD',
            createdAt: new \DateTimeImmutable(),
        );
    }

    private function createPaymentItem(string $id, float $amount): PaymentItemData
    {
        return new PaymentItemData(
            paymentId: $id,
            vendorId: 'VENDOR-' . substr($id, -3),
            vendorName: 'Test Vendor ' . substr($id, -3),
            amount: Money::of($amount, 'USD'),
            routingNumber: '123456789',
            accountNumber: '1234567890',
            paymentReference: 'INV-' . substr($id, -3),
        );
    }
}
