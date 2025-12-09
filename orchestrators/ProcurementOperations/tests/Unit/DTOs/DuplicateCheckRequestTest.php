<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\DuplicateCheckRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DuplicateCheckRequest::class)]
final class DuplicateCheckRequestTest extends TestCase
{
    public function test_can_create_request(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertEquals('tenant-1', $request->tenantId);
        $this->assertEquals('vendor-1', $request->vendorId);
        $this->assertEquals('INV-2024-001', $request->invoiceNumber);
        $this->assertEquals(1000.00, $request->invoiceAmount->getAmount());
        $this->assertEquals('2024-01-15', $request->invoiceDate->format('Y-m-d'));
    }

    public function test_default_lookback_days(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertEquals(365, $request->lookbackDays);
    }

    public function test_custom_lookback_days(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lookbackDays: 90,
        );

        $this->assertEquals(90, $request->lookbackDays);
    }

    public function test_strict_mode_default_false(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertFalse($request->strictMode);
    }

    #[DataProvider('invoiceNumberNormalizationProvider')]
    public function test_normalized_invoice_number(string $input, string $expected): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: $input,
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertEquals($expected, $request->getNormalizedInvoiceNumber());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invoiceNumberNormalizationProvider(): array
    {
        return [
            'spaces_removed' => ['INV 2024 001', 'INV2024001'],
            'dashes_removed' => ['INV-2024-001', 'INV2024001'],
            'underscores_removed' => ['INV_2024_001', 'INV2024001'],
            'lowercase_to_uppercase' => ['inv-2024-001', 'INV2024001'],
            'leading_zeros_trimmed' => ['INV-00123', 'INV123'],
            'mixed_format' => ['inv 00-123_456', 'INV123456'],
            'already_clean' => ['INV2024001', 'INV2024001'],
        ];
    }

    public function test_generate_fingerprint_is_deterministic(): void
    {
        $request1 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $request2 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertEquals($request1->generateFingerprint(), $request2->generateFingerprint());
    }

    public function test_fingerprint_changes_with_different_vendor(): void
    {
        $request1 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $request2 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-2', // Different vendor
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertNotEquals($request1->generateFingerprint(), $request2->generateFingerprint());
    }

    public function test_fingerprint_changes_with_different_amount(): void
    {
        $request1 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $request2 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(200.00, 'MYR'), // Different amount
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertNotEquals($request1->generateFingerprint(), $request2->generateFingerprint());
    }

    public function test_fingerprint_same_for_normalized_invoice_numbers(): void
    {
        $request1 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $request2 = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'inv 001', // Different format, same normalized
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertEquals($request1->generateFingerprint(), $request2->generateFingerprint());
    }

    public function test_get_lookback_date(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-06-15');
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-001',
            invoiceAmount: Money::of(100.00, 'MYR'),
            invoiceDate: $invoiceDate,
            lookbackDays: 90,
        );

        $lookbackDate = $request->getLookbackDate();
        $this->assertEquals('2024-03-17', $lookbackDate->format('Y-m-d'));
    }
}
