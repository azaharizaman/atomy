<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Strategies\BankFile\SwiftMt101Generator;
use Nexus\ProcurementOperations\ValueObjects\SwiftMt101Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SwiftMt101Generator::class)]
final class SwiftMt101GeneratorTest extends TestCase
{
    private SwiftMt101Configuration $configuration;
    private SwiftMt101Generator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new SwiftMt101Configuration(
            senderBic: 'ABCDUS33XXX',
            orderingCustomerAccount: '123456789012',
            orderingCustomerName: 'ACME CORPORATION',
            accountServicingInstitution: 'EFGHGB2LXXX',
            defaultChargeCode: 'SHA',
        );

        $this->generator = new SwiftMt101Generator($this->configuration, new NullLogger());
    }

    #[Test]
    public function it_returns_correct_format(): void
    {
        $this->assertSame(BankFileFormat::SWIFT_MT101, $this->generator->getFormat());
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

        // Empty batch is not supported - no payment items to process
        $this->assertFalse($this->generator->supports($batch));
    }

    #[Test]
    public function it_supports_international_currencies(): void
    {
        $currencies = ['EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD'];

        foreach ($currencies as $currency) {
            $batch = $this->createBatchWithCurrency($currency);

            $this->assertTrue(
                $this->generator->supports($batch),
                "Should support {$currency} currency",
            );
        }
    }

    #[Test]
    public function it_validates_batch_with_valid_data(): void
    {
        $batch = $this->createValidBatch();

        $errors = $this->generator->validate($batch);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_validates_batch_with_missing_beneficiary_info(): void
    {
        $batch = $this->createBatchWithMissingBeneficiaryInfo();

        $errors = $this->generator->validate($batch);

        $this->assertNotEmpty($errors);
    }

    #[Test]
    public function it_generates_valid_swift_mt101_message(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertSame(BankFileFormat::SWIFT_MT101, $result->getFormat());
    }

    #[Test]
    public function it_generates_message_with_correct_blocks(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // SWIFT messages have block structure
        $this->assertStringContainsString('{1:', $content, 'Should contain Block 1 (Basic Header)');
        $this->assertStringContainsString('{2:', $content, 'Should contain Block 2 (Application Header)');
        $this->assertStringContainsString('{4:', $content, 'Should contain Block 4 (Text Block)');
    }

    #[Test]
    public function it_includes_ordering_customer_in_message(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // Tag :50H: is ordering customer
        $this->assertStringContainsString(':50H:', $content);
        $this->assertStringContainsString('ACME CORPORATION', $content);
    }

    #[Test]
    public function it_includes_transaction_amount(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // Tag :32B: is currency/amount
        $this->assertStringContainsString(':32B:', $content);
        $this->assertStringContainsString('EUR', $content);
    }

    #[Test]
    public function it_includes_beneficiary_info(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // Tag :59: is beneficiary
        $this->assertStringContainsString(':59:', $content);
    }

    #[Test]
    public function it_generates_unique_message_reference_numbers(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('message_reference_number', $array);
        $this->assertNotEmpty($array['message_reference_number']);
    }

    #[Test]
    public function it_includes_sender_bic_in_result(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('sender_bic', $array);
        $this->assertSame('ABCDUS33XXX', $array['sender_bic']);
    }

    #[Test]
    public function it_includes_transaction_references(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $array = $result->toArray();

        $this->assertArrayHasKey('transaction_references', $array);
        $this->assertIsArray($array['transaction_references']);
        $this->assertCount(3, $array['transaction_references']);
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
    public function it_generates_suggested_filename(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $filename = $result->getFileName();

        $this->assertStringStartsWith('SWIFT_MT101_', $filename);
        $this->assertStringEndsWith('.fin', $filename);
    }

    #[Test]
    #[DataProvider('chargeCodeProvider')]
    public function it_supports_all_charge_codes(string $chargeCode): void
    {
        $config = new SwiftMt101Configuration(
            senderBic: 'ABCDUS33XXX',
            orderingCustomerAccount: '123456789012',
            orderingCustomerName: 'TEST COMPANY',
            defaultChargeCode: $chargeCode,
        );

        $generator = new SwiftMt101Generator($config, new NullLogger());
        $batch = $this->createValidBatch();

        $result = $generator->generate($batch);

        $this->assertTrue($result->isSuccess());
        $content = $result->getFileContent();
        $this->assertStringContainsString(':71A:' . $chargeCode, $content);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function chargeCodeProvider(): array
    {
        return [
            'SHA - Shared' => ['SHA'],
            'OUR - Ordering pays' => ['OUR'],
            'BEN - Beneficiary pays' => ['BEN'],
        ];
    }

    #[Test]
    public function it_calculates_checksum(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);

        $checksum = $result->getChecksum();

        $this->assertNotEmpty($checksum);
        $this->assertSame(64, strlen($checksum));
    }

    #[Test]
    public function it_uses_iban_when_available(): void
    {
        $batch = $this->createValidBatch();

        $result = $this->generator->generate($batch);
        $content = $result->getFileContent();

        // Should contain IBAN
        $this->assertStringContainsString('DE89370400440532013000', $content);
    }

    // ===== Helper Methods =====

    private function createValidBatch(): PaymentBatchData
    {
        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PB-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );

        return $batch
            ->withPaymentItem($this->createInternationalPayment('PAY-001', 1000.00))
            ->withPaymentItem($this->createInternationalPayment('PAY-002', 2500.00))
            ->withPaymentItem($this->createInternationalPayment('PAY-003', 750.50));
    }

    private function createEmptyBatch(): PaymentBatchData
    {
        return PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PB-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );
    }

    private function createBatchWithCurrency(string $currency): PaymentBatchData
    {
        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PB-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: $currency,
            createdBy: 'test-user',
        );

        return $batch->withPaymentItem(
            PaymentItemData::forInternationalWire(
                paymentItemId: 'PAY-001',
                vendorId: 'VENDOR-001',
                vendorName: 'Test Vendor',
                amount: Money::of(1000.00, $currency),
                invoiceIds: ['INV-001'],
                paymentReference: 'INV-001',
                beneficiaryBic: 'ZYXWDE33XXX',
                beneficiaryIban: 'DE89370400440532013000',
                beneficiaryName: 'Test Beneficiary',
            ),
        );
    }

    private function createBatchWithMissingBeneficiaryInfo(): PaymentBatchData
    {
        $batch = PaymentBatchData::create(
            batchId: 'BATCH-001',
            batchNumber: 'PB-2024-001',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );

        // Create payment without BIC/IBAN using base constructor
        $payment = new PaymentItemData(
            paymentItemId: 'PAY-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            amount: Money::of(1000.00, 'EUR'),
            invoiceIds: ['INV-001'],
            paymentReference: 'INV-001',
            status: 'pending',
            // No beneficiaryBic or beneficiaryIban
        );

        return $batch->withPaymentItem($payment);
    }

    private function createInternationalPayment(string $id, float $amount): PaymentItemData
    {
        return PaymentItemData::forInternationalWire(
            paymentItemId: $id,
            vendorId: 'VENDOR-' . substr($id, -3),
            vendorName: 'International Vendor ' . substr($id, -3),
            amount: Money::of($amount, 'EUR'),
            invoiceIds: ['INV-' . substr($id, -3)],
            paymentReference: 'INV-' . substr($id, -3),
            beneficiaryBic: 'ZYXWDE33XXX',
            beneficiaryIban: 'DE89370400440532013000',
            beneficiaryName: 'Empfänger GmbH',
            beneficiaryAddress: 'Berlin, Germany',
            beneficiaryCountry: 'DE',
        );
    }
}
