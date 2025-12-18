<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use Nexus\Crypto\Enums\MaskingPattern;
use Nexus\Crypto\Services\DataMasker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(DataMasker::class)]
final class DataMaskerTest extends TestCase
{
    private DataMasker $masker;

    protected function setUp(): void
    {
        $this->masker = new DataMasker(new NullLogger());
    }

    // =====================================================
    // MASK WITH PREDEFINED PATTERN TESTS
    // =====================================================

    #[DataProvider('maskPatternProvider')]
    public function test_mask_applies_pattern_correctly(
        string $input,
        MaskingPattern $pattern,
        string $expectedContains
    ): void {
        $result = $this->masker->mask($input, $pattern);

        $this->assertStringContainsString($expectedContains, $result);
    }

    public static function maskPatternProvider(): array
    {
        return [
            'email pattern' => ['john.doe@example.com', MaskingPattern::EMAIL, '@example.com'],
            'phone pattern' => ['+60123456789', MaskingPattern::PHONE, '6789'],
            'credit card pattern' => ['4111111111111111', MaskingPattern::CREDIT_CARD, '1111'],
            'name pattern' => ['John Doe', MaskingPattern::NAME, '*'],
            'iban pattern' => ['DE89370400440532013000', MaskingPattern::IBAN, 'DE89'],
        ];
    }

    public function test_mask_with_full_redaction(): void
    {
        $result = $this->masker->mask('sensitive data here', MaskingPattern::FULL_REDACTION);

        $this->assertSame('[REDACTED]', $result);
    }

    // =====================================================
    // MASK WITH CUSTOM PATTERN TESTS
    // =====================================================

    public function test_mask_with_custom_pattern_preserves_marked_chars(): void
    {
        $result = $this->masker->maskWithPattern('1234567890', '####-****-##');

        $this->assertSame('1234-****-90', $result);
    }

    public function test_mask_with_custom_pattern_uses_custom_mask_char(): void
    {
        $result = $this->masker->maskWithPattern('ABCDEF', '##****', '#');

        $this->assertSame('AB####', $result);
    }

    public function test_mask_with_custom_pattern_handles_literal_characters(): void
    {
        $result = $this->masker->maskWithPattern('1234', '(##)**');

        $this->assertSame('(12)**', $result);
    }

    public function test_mask_with_custom_pattern_handles_longer_data(): void
    {
        $result = $this->masker->maskWithPattern('ABCDEFGHIJ', '####');

        // Pattern is 4 chars, data is 10 chars - remaining masked
        $this->assertSame('ABCD******', $result);
    }

    // =====================================================
    // EMAIL MASKING TESTS
    // =====================================================

    public function test_mask_email_preserves_domain(): void
    {
        $result = $this->masker->maskEmail('john.doe@example.com');

        $this->assertStringContainsString('@example.com', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_email_shows_first_character(): void
    {
        $result = $this->masker->maskEmail('john.doe@example.com');

        $this->assertStringStartsWith('j', $result);
    }

    public function test_mask_email_handles_short_local_part(): void
    {
        $result = $this->masker->maskEmail('ab@test.com');

        $this->assertStringContainsString('@test.com', $result);
    }

    public function test_mask_email_handles_single_char_local_part(): void
    {
        $result = $this->masker->maskEmail('a@test.com');

        $this->assertSame('a@test.com', $result);
    }

    public function test_mask_email_invalid_format_masks_partially(): void
    {
        $result = $this->masker->maskEmail('notanemail');

        $this->assertStringContainsString('*', $result);
        $this->assertStringStartsWith('n', $result);
    }

    // =====================================================
    // PHONE MASKING TESTS
    // =====================================================

    public function test_mask_phone_shows_last_four_digits(): void
    {
        $result = $this->masker->maskPhone('+60123456789');

        $this->assertStringEndsWith('6789', $result);
    }

    public function test_mask_phone_preserves_format(): void
    {
        $result = $this->masker->maskPhone('(555) 123-4567');

        // Format chars preserved
        $this->assertStringContainsString('(', $result);
        $this->assertStringContainsString(')', $result);
        $this->assertStringContainsString('-', $result);
    }

    public function test_mask_phone_short_number_returns_as_is(): void
    {
        $result = $this->masker->maskPhone('1234');

        // Too short to mask meaningfully
        $this->assertSame('1234', $result);
    }

    public function test_mask_phone_handles_plus_prefix(): void
    {
        $result = $this->masker->maskPhone('+1234567890');

        $this->assertStringStartsWith('+', $result);
    }

    // =====================================================
    // CREDIT CARD MASKING TESTS
    // =====================================================

    public function test_mask_credit_card_shows_first_six_and_last_four(): void
    {
        $result = $this->masker->maskCreditCard('4111111111111111');

        // PCI-DSS compliant: first 6 + last 4 visible
        $this->assertStringStartsWith('411111', $result);
        $this->assertStringEndsWith('1111', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_credit_card_preserves_format_with_spaces(): void
    {
        $result = $this->masker->maskCreditCard('4111 1111 1111 1111');

        $this->assertStringEndsWith('1111', $result);
    }

    public function test_mask_credit_card_preserves_format_with_dashes(): void
    {
        $result = $this->masker->maskCreditCard('4111-1111-1111-1111');

        $this->assertStringEndsWith('1111', $result);
    }

    public function test_mask_credit_card_amex_15_digits(): void
    {
        $result = $this->masker->maskCreditCard('378282246310005');

        $this->assertStringEndsWith('0005', $result);
    }

    // =====================================================
    // NATIONAL ID MASKING TESTS
    // =====================================================

    public function test_mask_national_id_malaysian_ic(): void
    {
        $result = $this->masker->maskNationalId('880101145678', 'MY');

        // Shows first 2 (birth year) and last 4
        $this->assertStringContainsString('88', $result);
        $this->assertStringEndsWith('5678', $result);
    }

    public function test_mask_national_id_us_ssn(): void
    {
        $result = $this->masker->maskNationalId('123-45-6789', 'US');

        // Shows only last 4 digits
        $this->assertStringEndsWith('6789', $result);
        $this->assertStringContainsString('-', $result);
    }

    public function test_mask_national_id_uk_nino(): void
    {
        $result = $this->masker->maskNationalId('AB123456C', 'GB');

        // Shows prefix and suffix
        $this->assertStringStartsWith('AB', $result);
        $this->assertStringEndsWith('C', $result);
    }

    public function test_mask_national_id_singapore_nric(): void
    {
        $result = $this->masker->maskNationalId('S1234567D', 'SG');

        // Shows first letter and last 4
        $this->assertStringStartsWith('S', $result);
        $this->assertStringEndsWith('567D', $result);
    }

    public function test_mask_national_id_unknown_country(): void
    {
        $result = $this->masker->maskNationalId('1234567890', 'XX');

        $this->assertStringContainsString('*', $result);
    }

    // =====================================================
    // IBAN MASKING TESTS
    // =====================================================

    public function test_mask_iban_preserves_country_and_check(): void
    {
        $result = $this->masker->maskIban('DE89370400440532013000');

        $this->assertStringStartsWith('DE89', $result);
    }

    public function test_mask_iban_shows_last_four(): void
    {
        $result = $this->masker->maskIban('GB82WEST12345698765432');

        // Last 4 digits visible (may include space formatting)
        $this->assertStringContainsString('5432', str_replace(' ', '', $result));
    }

    public function test_mask_iban_with_spaces(): void
    {
        $result = $this->masker->maskIban('DE89 3704 0044 0532 0130 00');

        $this->assertStringStartsWith('DE89', $result);
        // Result should be formatted with spaces
        $this->assertStringContainsString(' ', $result);
    }

    public function test_mask_iban_short_returns_partially_masked(): void
    {
        $result = $this->masker->maskIban('DE89123456');

        $this->assertStringContainsString('*', $result);
    }

    // =====================================================
    // NAME MASKING TESTS
    // =====================================================

    public function test_mask_name_preserves_initials(): void
    {
        $result = $this->masker->maskName('John Doe');

        $this->assertStringStartsWith('J', $result);
        $this->assertStringContainsString('D', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_name_single_name(): void
    {
        $result = $this->masker->maskName('John');

        $this->assertStringStartsWith('J', $result);
        $this->assertSame('J***', $result);
    }

    public function test_mask_name_multiple_names(): void
    {
        $result = $this->masker->maskName('John Michael Doe');

        $parts = explode(' ', $result);
        $this->assertCount(3, $parts);
        
        foreach ($parts as $part) {
            // Each part should start with letter and have asterisks
            $this->assertMatchesRegularExpression('/^[A-Z]\*+$/', $part);
        }
    }

    public function test_mask_name_empty_returns_empty(): void
    {
        $result = $this->masker->maskName('');

        $this->assertSame('', $result);
    }

    // =====================================================
    // ADDRESS MASKING TESTS
    // =====================================================

    public function test_mask_address_preserves_structure(): void
    {
        $result = $this->masker->maskAddress("123 Main Street\nKuala Lumpur\n50000");

        // Address should be joined with commas
        $this->assertStringContainsString(',', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_address_single_line(): void
    {
        $result = $this->masker->maskAddress('123 Main Street, Kuala Lumpur');

        $this->assertStringContainsString('*', $result);
    }

    // =====================================================
    // DATE OF BIRTH MASKING TESTS
    // =====================================================

    public function test_mask_date_of_birth_iso_format(): void
    {
        $result = $this->masker->maskDateOfBirth('1990-05-15');

        // Year visible, day/month masked
        $this->assertStringContainsString('1990', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_date_of_birth_slash_format(): void
    {
        $result = $this->masker->maskDateOfBirth('15/05/1990');

        $this->assertStringContainsString('1990', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_date_of_birth_european_format(): void
    {
        $result = $this->masker->maskDateOfBirth('15.05.1990');

        $this->assertStringContainsString('1990', $result);
        $this->assertStringContainsString('*', $result);
    }

    public function test_mask_date_of_birth_unknown_format(): void
    {
        $result = $this->masker->maskDateOfBirth('May 15, 1990');

        // Falls back to showing last 4 chars (year)
        $this->assertStringEndsWith('1990', $result);
    }

    // =====================================================
    // REDACTION TESTS
    // =====================================================

    public function test_redact_replaces_with_default_marker(): void
    {
        $result = $this->masker->redact('any sensitive data');

        $this->assertSame('[REDACTED]', $result);
    }

    public function test_redact_with_custom_marker(): void
    {
        $result = $this->masker->redact('sensitive', '[REMOVED]');

        $this->assertSame('[REMOVED]', $result);
    }

    public function test_redact_empty_string(): void
    {
        $result = $this->masker->redact('');

        $this->assertSame('[REDACTED]', $result);
    }

    // =====================================================
    // IS ALREADY MASKED TESTS
    // =====================================================

    public function test_is_already_masked_detects_asterisks(): void
    {
        $result = $this->masker->isAlreadyMasked('****1234');

        $this->assertTrue($result);
    }

    public function test_is_already_masked_detects_redaction_marker(): void
    {
        $result = $this->masker->isAlreadyMasked('[REDACTED]');

        $this->assertTrue($result);
    }

    public function test_is_already_masked_returns_false_for_plain_text(): void
    {
        $result = $this->masker->isAlreadyMasked('john.doe@example.com');

        $this->assertFalse($result);
    }

    public function test_is_already_masked_with_custom_mask_char(): void
    {
        $result = $this->masker->isAlreadyMasked('####1234', '#');

        $this->assertTrue($result);
    }

    public function test_is_already_masked_partial_masking(): void
    {
        // Only one asterisk out of 10 chars - less than 30% threshold
        $result = $this->masker->isAlreadyMasked('hello*world');

        $this->assertFalse($result);
    }

    // =====================================================
    // INTERFACE COMPLIANCE TESTS
    // =====================================================

    public function test_implements_data_masker_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\Crypto\Contracts\DataMaskerInterface::class,
            $this->masker
        );
    }

    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(DataMasker::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    // =====================================================
    // CUSTOM MASK CHARACTER TESTS
    // =====================================================

    public function test_constructor_accepts_custom_mask_char(): void
    {
        $masker = new DataMasker(new NullLogger(), '#');

        $result = $masker->maskEmail('john.doe@example.com');

        $this->assertStringContainsString('#', $result);
        $this->assertStringNotContainsString('*', $result);
    }

    // =====================================================
    // LOGGING TESTS
    // =====================================================

    public function test_logs_masking_operations(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('debug');

        $masker = new DataMasker($logger);
        $masker->mask('test@example.com', MaskingPattern::EMAIL);
    }
}
