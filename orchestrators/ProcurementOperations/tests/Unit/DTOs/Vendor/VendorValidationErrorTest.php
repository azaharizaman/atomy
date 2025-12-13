<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorValidationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorValidationError::class)]
final class VendorValidationErrorTest extends TestCase
{
    #[Test]
    public function it_creates_required_field_error(): void
    {
        $error = VendorValidationError::required('vendor_name', 'Vendor name is required');

        $this->assertSame('vendor_name', $error->field);
        $this->assertSame('REQUIRED', $error->code);
        $this->assertSame('Vendor name is required', $error->message);
        $this->assertSame('error', $error->severity);
    }

    #[Test]
    public function it_creates_invalid_format_error(): void
    {
        $error = VendorValidationError::invalidFormat('email', 'Invalid email format');

        $this->assertSame('email', $error->field);
        $this->assertSame('INVALID_FORMAT', $error->code);
        $this->assertSame('Invalid email format', $error->message);
    }

    #[Test]
    public function it_creates_duplicate_error(): void
    {
        $error = VendorValidationError::duplicate('tax_id', 'Vendor with this Tax ID exists');

        $this->assertSame('tax_id', $error->field);
        $this->assertSame('DUPLICATE', $error->code);
    }

    #[Test]
    public function it_creates_invalid_country_error(): void
    {
        $error = VendorValidationError::invalidCountry('XYZ', 'Invalid country code');

        $this->assertSame('country_code', $error->field);
        $this->assertSame('INVALID_COUNTRY', $error->code);
        $this->assertArrayHasKey('country_code', $error->context);
        $this->assertSame('XYZ', $error->context['country_code']);
    }

    #[Test]
    public function it_creates_sanctioned_entity_error(): void
    {
        $error = VendorValidationError::sanctionedEntity(
            'OFAC SDN List',
            0.95,
            ['OFAC SDN List', 'EU Consolidated List'],
        );

        $this->assertSame('sanctions_check', $error->field);
        $this->assertSame('SANCTIONED_ENTITY', $error->code);
        $this->assertSame('error', $error->severity);
        $this->assertSame('OFAC SDN List', $error->context['matched_list']);
        $this->assertSame(0.95, $error->context['confidence']);
    }

    #[Test]
    public function it_creates_expired_document_error(): void
    {
        $expiredDate = new \DateTimeImmutable('2024-01-15');
        $error = VendorValidationError::expiredDocument(
            'ISO 9001 Certification',
            $expiredDate,
        );

        $this->assertSame('documents', $error->field);
        $this->assertSame('EXPIRED_DOCUMENT', $error->code);
        $this->assertSame('ISO 9001 Certification', $error->context['document_name']);
        $this->assertSame('2024-01-15', $error->context['expired_date']);
    }

    #[Test]
    public function it_creates_expiring_document_warning(): void
    {
        $expiryDate = new \DateTimeImmutable('+15 days');
        $error = VendorValidationError::expiringDocument(
            'Insurance Certificate',
            $expiryDate,
            15,
        );

        $this->assertSame('documents', $error->field);
        $this->assertSame('EXPIRING_DOCUMENT', $error->code);
        $this->assertSame('warning', $error->severity);
        $this->assertSame(15, $error->context['days_until_expiry']);
    }

    #[Test]
    public function it_creates_missing_certification_error(): void
    {
        $error = VendorValidationError::missingCertification('ISO 27001');

        $this->assertSame('certifications', $error->field);
        $this->assertSame('MISSING_CERTIFICATION', $error->code);
        $this->assertSame('ISO 27001', $error->context['certification']);
    }

    #[Test]
    public function it_creates_invalid_bank_account_error(): void
    {
        $error = VendorValidationError::invalidBankAccount('Invalid IBAN format');

        $this->assertSame('bank_account', $error->field);
        $this->assertSame('INVALID_BANK_ACCOUNT', $error->code);
    }

    #[Test]
    public function it_creates_tax_id_mismatch_error(): void
    {
        $error = VendorValidationError::taxIdMismatch('TAX123', 'TAX456');

        $this->assertSame('tax_id', $error->field);
        $this->assertSame('TAX_ID_MISMATCH', $error->code);
        $this->assertSame('TAX123', $error->context['submitted_id']);
        $this->assertSame('TAX456', $error->context['official_id']);
    }

    #[Test]
    public function it_creates_generic_warning(): void
    {
        $error = VendorValidationError::warning(
            field: 'annual_volume',
            message: 'Volume below threshold for premium tier',
            context: ['threshold' => 100000],
        );

        $this->assertSame('annual_volume', $error->field);
        $this->assertSame('WARNING', $error->code);
        $this->assertSame('warning', $error->severity);
        $this->assertSame(100000, $error->context['threshold']);
    }

    #[Test]
    public function it_checks_if_blocking(): void
    {
        $error = VendorValidationError::required('field', 'Required');
        $warning = VendorValidationError::warning('field', 'Warning');

        $this->assertTrue($error->isBlocking());
        $this->assertFalse($warning->isBlocking());
    }

    #[Test]
    public function it_checks_if_compliance_related(): void
    {
        $complianceError = VendorValidationError::sanctionedEntity('OFAC', 0.9, []);
        $formatError = VendorValidationError::invalidFormat('email', 'Invalid');

        $this->assertTrue($complianceError->isComplianceRelated());
        $this->assertFalse($formatError->isComplianceRelated());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $error = VendorValidationError::required('vendor_name', 'Required field');

        $array = $error->toArray();

        $this->assertIsArray($array);
        $this->assertSame('vendor_name', $array['field']);
        $this->assertSame('REQUIRED', $array['code']);
        $this->assertSame('Required field', $array['message']);
        $this->assertSame('error', $array['severity']);
    }

    #[Test]
    #[DataProvider('complianceCodeProvider')]
    public function it_identifies_compliance_codes(string $code, bool $isCompliance): void
    {
        $error = new VendorValidationError(
            field: 'test',
            code: $code,
            message: 'Test',
            severity: 'error',
        );

        $this->assertSame($isCompliance, $error->isComplianceRelated());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function complianceCodeProvider(): array
    {
        return [
            'SANCTIONED_ENTITY is compliance' => ['SANCTIONED_ENTITY', true],
            'EXPIRED_DOCUMENT is compliance' => ['EXPIRED_DOCUMENT', true],
            'EXPIRING_DOCUMENT is compliance' => ['EXPIRING_DOCUMENT', true],
            'MISSING_CERTIFICATION is compliance' => ['MISSING_CERTIFICATION', true],
            'REQUIRED is not compliance' => ['REQUIRED', false],
            'INVALID_FORMAT is not compliance' => ['INVALID_FORMAT', false],
            'DUPLICATE is not compliance' => ['DUPLICATE', false],
        ];
    }
}
