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

    // ========================================
    // Security Tests - SWIFT Tag Injection Prevention
    // ========================================

    #[Test]
    public function it_sanitizes_vendor_name_to_prevent_swift_tag_injection(): void
    {
        // Attempt tag injection via vendor name
        $maliciousVendorName = ':71A:OUR MALICIOUS VENDOR';

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-SEC-001',
            batchNumber: 'PB-2024-SEC-001',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );

        $payment = PaymentItemData::forInternationalWire(
            paymentItemId: 'PAY-SEC-001',
            vendorId: 'VENDOR-SEC-001',
            vendorName: $maliciousVendorName,
            amount: Money::of(1000.00, 'EUR'),
            invoiceIds: ['INV-SEC-001'],
            paymentReference: 'REF-SEC-001',
            beneficiaryBic: 'DEUTDEFFXXX',
            beneficiaryIban: 'DE89370400440532013000',
            beneficiaryName: 'Legit Name',
            beneficiaryAddress: 'Berlin, Germany',
            beneficiaryCountry: 'DE',
        );

        $batch = $batch->withPaymentItem($payment);
        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());

        // The malicious tag should be stripped - no :71A: in vendor name line
        $content = $result->getFileContent();

        // The content should have exactly one :71A: tag (the legitimate one)
        $tagCount = substr_count($content, ':71A:');
        $this->assertSame(1, $tagCount, 'Should have exactly one :71A: tag (the legitimate charge code)');

        // The malicious vendor name should be sanitized to not start with a tag
        $this->assertStringNotContainsString(':71A:OUR MALICIOUS', $content);
    }

    #[Test]
    public function it_sanitizes_beneficiary_address_to_prevent_swift_tag_injection(): void
    {
        // Attempt tag injection via beneficiary address
        $maliciousAddress = ':59:/ATTACKER_IBAN_DE12345678901234567890';

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-SEC-002',
            batchNumber: 'PB-2024-SEC-002',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );

        $payment = PaymentItemData::forInternationalWire(
            paymentItemId: 'PAY-SEC-002',
            vendorId: 'VENDOR-SEC-002',
            vendorName: 'Legitimate Vendor',
            amount: Money::of(2000.00, 'EUR'),
            invoiceIds: ['INV-SEC-002'],
            paymentReference: 'REF-SEC-002',
            beneficiaryBic: 'DEUTDEFFXXX',
            beneficiaryIban: 'DE89370400440532013000',
            beneficiaryName: 'Legit Name',
            beneficiaryAddress: $maliciousAddress,
            beneficiaryCountry: 'DE',
        );

        $batch = $batch->withPaymentItem($payment);
        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());

        $content = $result->getFileContent();

        // Should have exactly one :59: tag (the legitimate beneficiary)
        $tagCount = substr_count($content, ':59:');
        $this->assertSame(1, $tagCount, 'Should have exactly one :59: tag (the legitimate beneficiary)');

        // The malicious address should not appear as a SWIFT tag
        $this->assertStringNotContainsString(':59:/ATTACKER', $content);
    }

    #[Test]
    public function it_sanitizes_bank_name_to_prevent_swift_tag_injection(): void
    {
        // Attempt tag injection via bank name (when using routing number path)
        $maliciousBankName = ':57A:ATTACKERBIC';

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-SEC-003',
            batchNumber: 'PB-2024-SEC-003',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'test-user',
        );

        // Create payment using routing number (no BIC) to exercise :57D: path
        $payment = new PaymentItemData(
            paymentItemId: 'PAY-SEC-003',
            vendorId: 'VENDOR-SEC-003',
            vendorName: 'Domestic Vendor',
            amount: Money::of(3000.00, 'USD'),
            invoiceIds: ['INV-SEC-003'],
            paymentReference: 'REF-SEC-003',
            status: 'pending',
            vendorBankAccountNumber: '123456789012',
            vendorBankRoutingNumber: '021000021',
            vendorBankName: $maliciousBankName,
        );

        $batch = $batch->withPaymentItem($payment);
        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());

        $content = $result->getFileContent();

        // The malicious bank name should not create a :57A: tag
        $this->assertStringNotContainsString(':57A:ATTACKERBIC', $content);

        // Should have exactly one :57D: tag (the legitimate one via routing number)
        $tagCount = substr_count($content, ':57D:');
        $this->assertSame(1, $tagCount, 'Should have exactly one :57D: tag');
    }

    #[Test]
    public function it_strips_leading_colons_from_untrusted_text(): void
    {
        // Various injection attempts with leading colons
        $maliciousName = ':::SUSPICIOUS VENDOR';

        $batch = PaymentBatchData::create(
            batchId: 'BATCH-SEC-004',
            batchNumber: 'PB-2024-SEC-004',
            tenantId: 'tenant-123',
            paymentMethod: 'wire',
            bankAccountId: 'bank-account-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'EUR',
            createdBy: 'test-user',
        );

        $payment = PaymentItemData::forInternationalWire(
            paymentItemId: 'PAY-SEC-004',
            vendorId: 'VENDOR-SEC-004',
            vendorName: $maliciousName,
            amount: Money::of(500.00, 'EUR'),
            invoiceIds: ['INV-SEC-004'],
            paymentReference: 'REF-SEC-004',
            beneficiaryBic: 'DEUTDEFFXXX',
            beneficiaryIban: 'DE89370400440532013000',
            beneficiaryName: 'Legit Name',
            beneficiaryAddress: '123 Main Street',
            beneficiaryCountry: 'DE',
        );

        $batch = $batch->withPaymentItem($payment);
        $result = $this->generator->generate($batch);

        $this->assertTrue($result->isSuccess());

        $content = $result->getFileContent();

        // Leading colons should be stripped - vendor name should not start with colons
        // The sanitized name should appear without leading colons
        $this->assertStringContainsString('SUSPICIOUS VENDOR', $content);
        $this->assertStringNotContainsString(':::SUSPICIOUS', $content);
    }
}
