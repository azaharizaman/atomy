<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Enums;

use Nexus\Crypto\Enums\MaskingPattern;
use PHPUnit\Framework\TestCase;

final class MaskingPatternTest extends TestCase
{
    // =====================================================
    // BASIC ENUM TESTS
    // =====================================================

    public function test_all_cases_exist(): void
    {
        $cases = MaskingPattern::cases();
        
        $this->assertCount(8, $cases);
        $this->assertContains(MaskingPattern::EMAIL, $cases);
        $this->assertContains(MaskingPattern::PHONE, $cases);
        $this->assertContains(MaskingPattern::CREDIT_CARD, $cases);
        $this->assertContains(MaskingPattern::IBAN, $cases);
        $this->assertContains(MaskingPattern::NAME, $cases);
        $this->assertContains(MaskingPattern::ADDRESS, $cases);
        $this->assertContains(MaskingPattern::DATE_OF_BIRTH, $cases);
        $this->assertContains(MaskingPattern::FULL_REDACTION, $cases);
    }

    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('email', MaskingPattern::EMAIL->value);
        $this->assertSame('phone', MaskingPattern::PHONE->value);
        $this->assertSame('credit_card', MaskingPattern::CREDIT_CARD->value);
        $this->assertSame('iban', MaskingPattern::IBAN->value);
        $this->assertSame('name', MaskingPattern::NAME->value);
        $this->assertSame('address', MaskingPattern::ADDRESS->value);
        $this->assertSame('date_of_birth', MaskingPattern::DATE_OF_BIRTH->value);
        $this->assertSame('full_redaction', MaskingPattern::FULL_REDACTION->value);
    }

    // =====================================================
    // GET LABEL TESTS
    // =====================================================

    public function test_get_label_returns_human_readable_names(): void
    {
        $this->assertSame('Email Address', MaskingPattern::EMAIL->getLabel());
        $this->assertSame('Phone Number', MaskingPattern::PHONE->getLabel());
        $this->assertSame('Credit Card', MaskingPattern::CREDIT_CARD->getLabel());
        $this->assertSame('IBAN / Bank Account', MaskingPattern::IBAN->getLabel());
        $this->assertSame('Personal Name', MaskingPattern::NAME->getLabel());
        $this->assertSame('Mailing Address', MaskingPattern::ADDRESS->getLabel());
        $this->assertSame('Date of Birth', MaskingPattern::DATE_OF_BIRTH->getLabel());
        $this->assertSame('Full Redaction', MaskingPattern::FULL_REDACTION->getLabel());
    }

    // =====================================================
    // GET EXAMPLE TESTS
    // =====================================================

    public function test_get_example_returns_masked_examples(): void
    {
        $this->assertSame('j*******@example.com', MaskingPattern::EMAIL->getExample());
        $this->assertSame('+1 (***) ***-4567', MaskingPattern::PHONE->getExample());
        $this->assertSame('****-****-****-1234', MaskingPattern::CREDIT_CARD->getExample());
        $this->assertSame('DE**************3000', MaskingPattern::IBAN->getExample());
        $this->assertSame('J*** D**', MaskingPattern::NAME->getExample());
        $this->assertSame('123 **** Street, ****', MaskingPattern::ADDRESS->getExample());
        $this->assertSame('****-**-15', MaskingPattern::DATE_OF_BIRTH->getExample());
        $this->assertSame('[REDACTED]', MaskingPattern::FULL_REDACTION->getExample());
    }

    // =====================================================
    // FORMAT PRESERVING TESTS
    // =====================================================

    public function test_email_is_format_preserving(): void
    {
        $this->assertTrue(MaskingPattern::EMAIL->isFormatPreserving());
    }

    public function test_phone_is_format_preserving(): void
    {
        $this->assertTrue(MaskingPattern::PHONE->isFormatPreserving());
    }

    public function test_credit_card_is_format_preserving(): void
    {
        $this->assertTrue(MaskingPattern::CREDIT_CARD->isFormatPreserving());
    }

    public function test_iban_is_format_preserving(): void
    {
        $this->assertTrue(MaskingPattern::IBAN->isFormatPreserving());
    }

    public function test_full_redaction_is_not_format_preserving(): void
    {
        $this->assertFalse(MaskingPattern::FULL_REDACTION->isFormatPreserving());
    }

    public function test_address_is_format_preserving(): void
    {
        $this->assertTrue(MaskingPattern::ADDRESS->isFormatPreserving());
    }

    // =====================================================
    // COMPLIANCE STANDARDS TESTS
    // =====================================================

    public function test_credit_card_complies_with_pci_dss(): void
    {
        $standards = MaskingPattern::CREDIT_CARD->getComplianceStandards();
        $this->assertContains('PCI-DSS', $standards);
    }

    public function test_email_complies_with_gdpr(): void
    {
        $standards = MaskingPattern::EMAIL->getComplianceStandards();
        $this->assertContains('GDPR', $standards);
    }

    public function test_name_complies_with_gdpr_and_hipaa(): void
    {
        $standards = MaskingPattern::NAME->getComplianceStandards();
        $this->assertContains('GDPR', $standards);
        $this->assertContains('HIPAA', $standards);
    }

    public function test_date_of_birth_complies_with_multiple_standards(): void
    {
        $standards = MaskingPattern::DATE_OF_BIRTH->getComplianceStandards();
        $this->assertContains('GDPR', $standards);
        $this->assertContains('HIPAA', $standards);
        $this->assertContains('PCI-DSS', $standards);
    }

    public function test_full_redaction_complies_with_all_major_standards(): void
    {
        $standards = MaskingPattern::FULL_REDACTION->getComplianceStandards();
        $this->assertContains('GDPR', $standards);
        $this->assertContains('HIPAA', $standards);
        $this->assertContains('PCI-DSS', $standards);
        $this->assertContains('PDPA', $standards);
    }
}
