<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\DocumentType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentType::class)]
final class DocumentTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = [
            'PASSPORT', 'NATIONAL_ID', 'DRIVERS_LICENSE', 'RESIDENCE_PERMIT',
            'REFUGEE_DOCUMENT', 'VOTER_ID', 'MILITARY_ID', 'GOVERNMENT_ID',
            'UTILITY_BILL', 'BANK_STATEMENT', 'TAX_DOCUMENT', 'RENTAL_AGREEMENT',
            'MORTGAGE_STATEMENT', 'CERTIFICATE_OF_INCORPORATION', 'ARTICLES_OF_ASSOCIATION',
            'BUSINESS_LICENSE', 'TAX_REGISTRATION', 'SHAREHOLDER_REGISTER',
            'BOARD_RESOLUTION', 'FINANCIAL_STATEMENTS', 'PROOF_OF_ADDRESS_BUSINESS',
            'UBO_DECLARATION', 'OWNERSHIP_STRUCTURE_CHART', 'TRUST_DEED',
            'SELFIE', 'LIVENESS_CHECK', 'VIDEO_VERIFICATION', 'SIGNATURE_SPECIMEN',
        ];

        $actualCases = array_map(fn(DocumentType $case) => $case->name, DocumentType::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    #[DataProvider('primaryIdDocumentsProvider')]
    public function it_identifies_primary_id_documents(DocumentType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isPrimaryId());
    }

    public static function primaryIdDocumentsProvider(): array
    {
        return [
            'passport is primary ID' => [DocumentType::PASSPORT, true],
            'national ID is primary ID' => [DocumentType::NATIONAL_ID, true],
            'drivers license is primary ID' => [DocumentType::DRIVERS_LICENSE, true],
            'residence permit is primary ID' => [DocumentType::RESIDENCE_PERMIT, true],
            'refugee document is primary ID' => [DocumentType::REFUGEE_DOCUMENT, true],
            'utility bill is not primary ID' => [DocumentType::UTILITY_BILL, false],
            'bank statement is not primary ID' => [DocumentType::BANK_STATEMENT, false],
        ];
    }

    #[Test]
    #[DataProvider('addressDocumentsProvider')]
    public function it_identifies_address_documents(DocumentType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isAddressDocument());
    }

    public static function addressDocumentsProvider(): array
    {
        return [
            'utility bill is address doc' => [DocumentType::UTILITY_BILL, true],
            'bank statement is address doc' => [DocumentType::BANK_STATEMENT, true],
            'tax document is address doc' => [DocumentType::TAX_DOCUMENT, true],
            'rental agreement is address doc' => [DocumentType::RENTAL_AGREEMENT, true],
            'passport is not address doc' => [DocumentType::PASSPORT, false],
        ];
    }

    #[Test]
    #[DataProvider('corporateDocumentsProvider')]
    public function it_identifies_corporate_documents(DocumentType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isCorporateDocument());
    }

    public static function corporateDocumentsProvider(): array
    {
        return [
            'certificate of incorporation' => [DocumentType::CERTIFICATE_OF_INCORPORATION, true],
            'articles of association' => [DocumentType::ARTICLES_OF_ASSOCIATION, true],
            'business license' => [DocumentType::BUSINESS_LICENSE, true],
            'passport is not corporate' => [DocumentType::PASSPORT, false],
        ];
    }

    #[Test]
    #[DataProvider('uboDocumentsProvider')]
    public function it_identifies_ubo_documents(DocumentType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isUboDocument());
    }

    public static function uboDocumentsProvider(): array
    {
        return [
            'ubo declaration' => [DocumentType::UBO_DECLARATION, true],
            'ownership structure chart' => [DocumentType::OWNERSHIP_STRUCTURE_CHART, true],
            'trust deed' => [DocumentType::TRUST_DEED, true],
            'shareholder register' => [DocumentType::SHAREHOLDER_REGISTER, true],
            'passport is not ubo doc' => [DocumentType::PASSPORT, false],
        ];
    }

    #[Test]
    #[DataProvider('expiryDocumentsProvider')]
    public function it_identifies_documents_with_expiry(DocumentType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->hasExpiry());
    }

    public static function expiryDocumentsProvider(): array
    {
        return [
            'passport has expiry' => [DocumentType::PASSPORT, true],
            'national ID has expiry' => [DocumentType::NATIONAL_ID, true],
            'drivers license has expiry' => [DocumentType::DRIVERS_LICENSE, true],
            'utility bill no expiry' => [DocumentType::UTILITY_BILL, false],
        ];
    }

    #[Test]
    public function it_returns_max_age_for_address_documents(): void
    {
        $this->assertSame(90, DocumentType::UTILITY_BILL->maxAgeDays());
        $this->assertSame(90, DocumentType::BANK_STATEMENT->maxAgeDays());
        $this->assertSame(365, DocumentType::TAX_DOCUMENT->maxAgeDays());
        $this->assertNull(DocumentType::PASSPORT->maxAgeDays());
    }

    #[Test]
    public function it_returns_verification_weight(): void
    {
        $this->assertSame(100, DocumentType::PASSPORT->verificationWeight());
        $this->assertSame(95, DocumentType::NATIONAL_ID->verificationWeight());
        $this->assertSame(85, DocumentType::DRIVERS_LICENSE->verificationWeight());
        $this->assertSame(60, DocumentType::UTILITY_BILL->verificationWeight());
    }

    #[Test]
    public function it_returns_human_readable_label(): void
    {
        $this->assertSame('Passport', DocumentType::PASSPORT->label());
        $this->assertSame('National ID Card', DocumentType::NATIONAL_ID->label());
        $this->assertSame("Driver's License", DocumentType::DRIVERS_LICENSE->label());
        $this->assertSame('Utility Bill', DocumentType::UTILITY_BILL->label());
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('passport', DocumentType::PASSPORT->value);
        $this->assertSame('national_id', DocumentType::NATIONAL_ID->value);
        $this->assertSame('drivers_license', DocumentType::DRIVERS_LICENSE->value);
    }
}
