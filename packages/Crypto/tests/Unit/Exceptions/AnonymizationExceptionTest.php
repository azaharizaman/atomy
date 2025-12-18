<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Exceptions;

use Nexus\Crypto\Exceptions\AnonymizationException;
use Nexus\Crypto\Exceptions\CryptoException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnonymizationException::class)]
final class AnonymizationExceptionTest extends TestCase
{
    // =====================================================
    // INHERITANCE TESTS
    // =====================================================

    public function test_extends_crypto_exception(): void
    {
        $exception = new AnonymizationException('Test message');

        $this->assertInstanceOf(CryptoException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    // =====================================================
    // FACTORY METHOD TESTS
    // =====================================================

    public function test_invalid_method_creates_exception_with_correct_message(): void
    {
        $exception = AnonymizationException::invalidMethod('unknown_method');

        $this->assertStringContainsString('unknown_method', $exception->getMessage());
        $this->assertStringContainsString('Invalid anonymization method', $exception->getMessage());
        $this->assertStringContainsString('AnonymizationMethod enum', $exception->getMessage());
    }

    public function test_invalid_method_with_various_inputs(): void
    {
        $methods = ['HASH_BASED', 'custom', '123', ''];

        foreach ($methods as $method) {
            $exception = AnonymizationException::invalidMethod($method);
            $this->assertStringContainsString($method, $exception->getMessage());
        }
    }

    public function test_missing_option_creates_exception_with_correct_message(): void
    {
        // Note: signature is missingOption(string $option, string $method)
        $exception = AnonymizationException::missingOption('keyId', 'HMAC_BASED');

        $this->assertStringContainsString('keyId', $exception->getMessage());
        $this->assertStringContainsString('HMAC_BASED', $exception->getMessage());
        $this->assertStringContainsString('requires', $exception->getMessage());
    }

    public function test_missing_option_with_various_options(): void
    {
        $testCases = [
            ['hierarchy', 'K_ANONYMITY'],
            ['keyId', 'HMAC_BASED'],
            ['salt', 'SALTED_HASH'],
        ];

        foreach ($testCases as [$option, $method]) {
            $exception = AnonymizationException::missingOption($option, $method);
            
            $this->assertStringContainsString($option, $exception->getMessage());
            $this->assertStringContainsString($method, $exception->getMessage());
        }
    }

    public function test_pseudonymization_failed_creates_exception_with_correct_message(): void
    {
        $reason = 'Key not found in storage';
        $exception = AnonymizationException::pseudonymizationFailed($reason);

        $this->assertStringContainsString('Pseudonymization failed', $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    public function test_pseudonymization_failed_with_various_reasons(): void
    {
        $reasons = [
            'Key not found',
            'Encryption error',
            'Invalid key format',
            '',
        ];

        foreach ($reasons as $reason) {
            $exception = AnonymizationException::pseudonymizationFailed($reason);
            $this->assertStringContainsString($reason, $exception->getMessage());
        }
    }

    public function test_de_pseudonymization_failed_creates_exception_with_correct_message(): void
    {
        $reason = 'Decryption failed: invalid ciphertext';
        $exception = AnonymizationException::dePseudonymizationFailed($reason);

        $this->assertStringContainsString('De-pseudonymization failed', $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    public function test_de_pseudonymization_failed_with_various_reasons(): void
    {
        $reasons = [
            'Key expired',
            'Authentication tag mismatch',
            'Invalid base64 encoding',
            'Invalid JSON format',
        ];

        foreach ($reasons as $reason) {
            $exception = AnonymizationException::dePseudonymizationFailed($reason);
            $this->assertStringContainsString($reason, $exception->getMessage());
        }
    }

    public function test_cannot_verify_non_deterministic_creates_exception(): void
    {
        $exception = AnonymizationException::cannotVerifyNonDeterministic('SALTED_HASH');

        $this->assertStringContainsString('SALTED_HASH', $exception->getMessage());
        $this->assertStringContainsString('non-deterministic', $exception->getMessage());
        $this->assertStringContainsString('HASH_BASED', $exception->getMessage());
        $this->assertStringContainsString('HMAC_BASED', $exception->getMessage());
    }

    public function test_invalid_hierarchy_creates_exception_with_correct_message(): void
    {
        $reason = 'hierarchy must be an array';
        $exception = AnonymizationException::invalidHierarchy($reason);

        $this->assertStringContainsString('Invalid generalization hierarchy', $exception->getMessage());
        $this->assertStringContainsString('k-anonymity', $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    public function test_encryption_failed_creates_exception_with_correct_message(): void
    {
        $exception = AnonymizationException::encryptionFailed('encrypt', 'Sodium error');

        $this->assertStringContainsString('Encryption', $exception->getMessage());
        $this->assertStringContainsString('encrypt', $exception->getMessage());
        $this->assertStringContainsString('pseudonymization', $exception->getMessage());
        $this->assertStringContainsString('Sodium error', $exception->getMessage());
    }

    public function test_encryption_failed_with_various_operations(): void
    {
        $operations = ['encrypt', 'decrypt', 'serialize'];

        foreach ($operations as $operation) {
            $exception = AnonymizationException::encryptionFailed($operation, 'test reason');
            $this->assertStringContainsString($operation, $exception->getMessage());
        }
    }

    // =====================================================
    // EXCEPTION PROPERTIES TESTS
    // =====================================================

    public function test_exception_message_is_accessible(): void
    {
        $exception = new AnonymizationException('Custom error message');

        $this->assertSame('Custom error message', $exception->getMessage());
    }

    public function test_exception_code_defaults_to_zero(): void
    {
        $exception = AnonymizationException::invalidMethod('test');

        $this->assertSame(0, $exception->getCode());
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('test_method');

        throw AnonymizationException::invalidMethod('test_method');
    }

    public function test_exception_can_be_caught_as_crypto_exception(): void
    {
        $this->expectException(CryptoException::class);

        throw AnonymizationException::pseudonymizationFailed('test');
    }

    // =====================================================
    // MESSAGE FORMAT TESTS
    // =====================================================

    public function test_all_factory_methods_return_anonymization_exception(): void
    {
        $exceptions = [
            AnonymizationException::invalidMethod('test'),
            AnonymizationException::missingOption('option', 'method'),
            AnonymizationException::pseudonymizationFailed('reason'),
            AnonymizationException::dePseudonymizationFailed('reason'),
            AnonymizationException::cannotVerifyNonDeterministic('method'),
            AnonymizationException::invalidHierarchy('reason'),
            AnonymizationException::encryptionFailed('operation', 'reason'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AnonymizationException::class, $exception);
        }
    }

    public function test_message_contains_actionable_information(): void
    {
        $exception = AnonymizationException::missingOption('hierarchy', 'K_ANONYMITY');

        $message = $exception->getMessage();
        
        // Should tell developer what's wrong and what's needed
        $this->assertStringContainsString('K_ANONYMITY', $message); // What method
        $this->assertStringContainsString('hierarchy', $message);   // What option
        $this->assertStringContainsString('requires', $message);    // The requirement
    }
}
