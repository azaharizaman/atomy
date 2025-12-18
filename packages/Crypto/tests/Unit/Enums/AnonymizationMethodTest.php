<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Enums;

use Nexus\Crypto\Enums\AnonymizationMethod;
use PHPUnit\Framework\TestCase;

final class AnonymizationMethodTest extends TestCase
{
    // =====================================================
    // BASIC ENUM TESTS
    // =====================================================

    public function test_all_cases_exist(): void
    {
        $cases = AnonymizationMethod::cases();
        
        $this->assertCount(5, $cases);
        $this->assertContains(AnonymizationMethod::HASH_BASED, $cases);
        $this->assertContains(AnonymizationMethod::SALTED_HASH, $cases);
        $this->assertContains(AnonymizationMethod::HMAC_BASED, $cases);
        $this->assertContains(AnonymizationMethod::K_ANONYMITY, $cases);
        $this->assertContains(AnonymizationMethod::SUPPRESSION, $cases);
    }

    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('hash_based', AnonymizationMethod::HASH_BASED->value);
        $this->assertSame('salted_hash', AnonymizationMethod::SALTED_HASH->value);
        $this->assertSame('hmac_based', AnonymizationMethod::HMAC_BASED->value);
        $this->assertSame('k_anonymity', AnonymizationMethod::K_ANONYMITY->value);
        $this->assertSame('suppression', AnonymizationMethod::SUPPRESSION->value);
    }

    // =====================================================
    // IS DETERMINISTIC TESTS
    // =====================================================

    public function test_hash_based_is_deterministic(): void
    {
        $this->assertTrue(AnonymizationMethod::HASH_BASED->isDeterministic());
    }

    public function test_salted_hash_is_not_deterministic(): void
    {
        $this->assertFalse(AnonymizationMethod::SALTED_HASH->isDeterministic());
    }

    public function test_hmac_based_is_deterministic(): void
    {
        $this->assertTrue(AnonymizationMethod::HMAC_BASED->isDeterministic());
    }

    public function test_k_anonymity_is_deterministic(): void
    {
        $this->assertTrue(AnonymizationMethod::K_ANONYMITY->isDeterministic());
    }

    public function test_suppression_is_deterministic(): void
    {
        $this->assertTrue(AnonymizationMethod::SUPPRESSION->isDeterministic());
    }

    // =====================================================
    // REQUIRES KEY TESTS
    // =====================================================

    public function test_hash_based_does_not_require_key(): void
    {
        $this->assertFalse(AnonymizationMethod::HASH_BASED->requiresKey());
    }

    public function test_salted_hash_does_not_require_key(): void
    {
        $this->assertFalse(AnonymizationMethod::SALTED_HASH->requiresKey());
    }

    public function test_hmac_based_requires_key(): void
    {
        $this->assertTrue(AnonymizationMethod::HMAC_BASED->requiresKey());
    }

    public function test_k_anonymity_does_not_require_key(): void
    {
        $this->assertFalse(AnonymizationMethod::K_ANONYMITY->requiresKey());
    }

    public function test_suppression_does_not_require_key(): void
    {
        $this->assertFalse(AnonymizationMethod::SUPPRESSION->requiresKey());
    }

    // =====================================================
    // REQUIRES OPTIONS TESTS
    // =====================================================

    public function test_hash_based_does_not_require_options(): void
    {
        $this->assertFalse(AnonymizationMethod::HASH_BASED->requiresOptions());
    }

    public function test_salted_hash_does_not_require_options(): void
    {
        $this->assertFalse(AnonymizationMethod::SALTED_HASH->requiresOptions());
    }

    public function test_hmac_based_requires_options(): void
    {
        $this->assertTrue(AnonymizationMethod::HMAC_BASED->requiresOptions());
    }

    public function test_k_anonymity_requires_options(): void
    {
        $this->assertTrue(AnonymizationMethod::K_ANONYMITY->requiresOptions());
    }

    public function test_suppression_does_not_require_options(): void
    {
        $this->assertFalse(AnonymizationMethod::SUPPRESSION->requiresOptions());
    }

    // =====================================================
    // GET REQUIRED OPTIONS TESTS
    // =====================================================

    public function test_hash_based_has_no_required_options(): void
    {
        $this->assertEmpty(AnonymizationMethod::HASH_BASED->getRequiredOptions());
    }

    public function test_hmac_based_requires_key_id_option(): void
    {
        $options = AnonymizationMethod::HMAC_BASED->getRequiredOptions();
        $this->assertContains('keyId', $options);
    }

    public function test_k_anonymity_requires_hierarchy_option(): void
    {
        $options = AnonymizationMethod::K_ANONYMITY->getRequiredOptions();
        $this->assertContains('hierarchy', $options);
    }

    // =====================================================
    // SECURITY LEVEL TESTS
    // =====================================================

    public function test_hash_based_has_low_security_level(): void
    {
        $this->assertSame('low', AnonymizationMethod::HASH_BASED->getSecurityLevel());
    }

    public function test_salted_hash_has_high_security_level(): void
    {
        $this->assertSame('high', AnonymizationMethod::SALTED_HASH->getSecurityLevel());
    }

    public function test_hmac_based_has_medium_security_level(): void
    {
        $this->assertSame('medium', AnonymizationMethod::HMAC_BASED->getSecurityLevel());
    }

    public function test_k_anonymity_has_medium_security_level(): void
    {
        $this->assertSame('medium', AnonymizationMethod::K_ANONYMITY->getSecurityLevel());
    }

    public function test_suppression_has_high_security_level(): void
    {
        $this->assertSame('high', AnonymizationMethod::SUPPRESSION->getSecurityLevel());
    }
}
