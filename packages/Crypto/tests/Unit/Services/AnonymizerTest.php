<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\AnonymizationException;
use Nexus\Crypto\Services\Anonymizer;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass(Anonymizer::class)]
final class AnonymizerTest extends TestCase
{
    private HasherInterface&MockObject $hasher;
    private SymmetricEncryptorInterface&MockObject $encryptor;
    private KeyStorageInterface&MockObject $keyStorage;
    private LoggerInterface $logger;
    private Anonymizer $anonymizer;

    protected function setUp(): void
    {
        $this->hasher = $this->createMock(HasherInterface::class);
        $this->encryptor = $this->createMock(SymmetricEncryptorInterface::class);
        $this->keyStorage = $this->createMock(KeyStorageInterface::class);
        $this->logger = new NullLogger();

        $this->anonymizer = new Anonymizer(
            $this->hasher,
            $this->encryptor,
            $this->keyStorage,
            $this->logger,
        );
    }

    // =====================================================
    // ANONYMIZE TESTS - HASH_BASED
    // =====================================================

    public function test_anonymize_with_hash_based_method(): void
    {
        $data = 'sensitive@email.com';
        $expectedHash = 'a1b2c3d4e5f6789012345678901234567890abcdef';

        $this->hasher
            ->expects($this->once())
            ->method('hash')
            ->with($data, HashAlgorithm::SHA256)
            ->willReturn(new HashResult(
                hash: $expectedHash,
                algorithm: HashAlgorithm::SHA256,
            ));

        $result = $this->anonymizer->anonymize($data, AnonymizationMethod::HASH_BASED);

        $this->assertInstanceOf(AnonymizedData::class, $result);
        $this->assertSame($expectedHash, $result->anonymizedValue);
        $this->assertSame(AnonymizationMethod::HASH_BASED, $result->method);
        $this->assertNull($result->salt);
        $this->assertTrue($result->isDeterministic());
    }

    // =====================================================
    // ANONYMIZE TESTS - SALTED_HASH
    // =====================================================

    public function test_anonymize_with_salted_hash_method(): void
    {
        $data = 'sensitive@email.com';
        $hashValue = 'hashed_value_here';

        // hasher->hash is called with salted data
        $this->hasher
            ->method('hash')
            ->willReturn(new HashResult(
                hash: $hashValue,
                algorithm: HashAlgorithm::SHA256,
            ));

        $result = $this->anonymizer->anonymize($data, AnonymizationMethod::SALTED_HASH);

        $this->assertInstanceOf(AnonymizedData::class, $result);
        $this->assertSame(AnonymizationMethod::SALTED_HASH, $result->method);
        
        // Salted hash returns salt:hash format
        $this->assertStringContainsString(':', $result->anonymizedValue);
        
        // Salt should be set
        $this->assertNotNull($result->salt);
        $this->assertSame(32, strlen($result->salt)); // 16 bytes = 32 hex chars
        
        // Not deterministic
        $this->assertFalse($result->isDeterministic());
    }

    public function test_salted_hash_produces_different_results_for_same_input(): void
    {
        $data = 'same_input@email.com';

        $this->hasher
            ->method('hash')
            ->willReturn(new HashResult(
                hash: 'hashed_value',
                algorithm: HashAlgorithm::SHA256,
            ));

        $result1 = $this->anonymizer->anonymize($data, AnonymizationMethod::SALTED_HASH);
        $result2 = $this->anonymizer->anonymize($data, AnonymizationMethod::SALTED_HASH);

        // Different salts should be generated
        $this->assertNotSame($result1->salt, $result2->salt);
        $this->assertNotSame($result1->anonymizedValue, $result2->anonymizedValue);
    }

    // =====================================================
    // ANONYMIZE TESTS - HMAC_BASED
    // =====================================================

    public function test_anonymize_with_hmac_based_method(): void
    {
        $data = 'sensitive@email.com';
        $keyId = 'hmac-key-001';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $result = $this->anonymizer->anonymize(
            $data,
            AnonymizationMethod::HMAC_BASED,
            ['keyId' => $keyId]
        );

        $this->assertInstanceOf(AnonymizedData::class, $result);
        $this->assertSame(AnonymizationMethod::HMAC_BASED, $result->method);
        $this->assertTrue($result->isDeterministic());
        
        // HMAC output should be 64 chars (SHA256 hex)
        $this->assertSame(64, strlen($result->anonymizedValue));
    }

    public function test_anonymize_hmac_based_throws_without_key_id(): void
    {
        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('keyId');

        $this->anonymizer->anonymize('data', AnonymizationMethod::HMAC_BASED);
    }

    // =====================================================
    // ANONYMIZE TESTS - K_ANONYMITY
    // =====================================================

    public function test_anonymize_with_k_anonymity_exact_match(): void
    {
        $data = '25';
        $hierarchy = [
            '25' => '20-30',
            '30' => '30-40',
        ];

        $result = $this->anonymizer->anonymize(
            $data,
            AnonymizationMethod::K_ANONYMITY,
            ['hierarchy' => $hierarchy]
        );

        $this->assertSame('20-30', $result->anonymizedValue);
        $this->assertSame(AnonymizationMethod::K_ANONYMITY, $result->method);
    }

    public function test_anonymize_with_k_anonymity_range_match(): void
    {
        $data = '27';  // Numeric value in range
        $hierarchy = [
            '20-30' => '20s age group',
            '30-40' => '30s age group',
        ];

        $result = $this->anonymizer->anonymize(
            $data,
            AnonymizationMethod::K_ANONYMITY,
            ['hierarchy' => $hierarchy]
        );

        $this->assertSame('20s age group', $result->anonymizedValue);
    }

    public function test_anonymize_with_k_anonymity_prefix_match(): void
    {
        $data = '12345';  // ZIP code
        $hierarchy = [
            '123*' => '123**',
        ];

        $result = $this->anonymizer->anonymize(
            $data,
            AnonymizationMethod::K_ANONYMITY,
            ['hierarchy' => $hierarchy]
        );

        $this->assertSame('123**', $result->anonymizedValue);
    }

    public function test_anonymize_with_k_anonymity_default_fallback(): void
    {
        $data = 'unknown_value';
        $hierarchy = [
            '25' => '20-30',
            'default' => '[GENERALIZED]',
        ];

        $result = $this->anonymizer->anonymize(
            $data,
            AnonymizationMethod::K_ANONYMITY,
            ['hierarchy' => $hierarchy]
        );

        $this->assertSame('[GENERALIZED]', $result->anonymizedValue);
    }

    public function test_anonymize_k_anonymity_throws_without_hierarchy(): void
    {
        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('hierarchy');

        $this->anonymizer->anonymize('25', AnonymizationMethod::K_ANONYMITY);
    }

    // =====================================================
    // ANONYMIZE TESTS - SUPPRESSION
    // =====================================================

    public function test_anonymize_with_suppression_method(): void
    {
        $data = 'any sensitive data here';

        $result = $this->anonymizer->anonymize($data, AnonymizationMethod::SUPPRESSION);

        $this->assertSame('[SUPPRESSED]', $result->anonymizedValue);
        $this->assertSame(AnonymizationMethod::SUPPRESSION, $result->method);
        // Suppression is deterministic (same output for all inputs)
        $this->assertTrue($result->isDeterministic());
        // But not correlatable (all outputs are identical)
        $this->assertFalse($result->isCorrelatable());
    }

    // =====================================================
    // PSEUDONYMIZE TESTS
    // =====================================================

    public function test_pseudonymize_encrypts_data(): void
    {
        $data = 'john@example.com';
        $keyId = 'customer-key-001';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $encryptedData = new EncryptedData(
            ciphertext: base64_encode('encrypted_ciphertext'),
            iv: base64_encode(random_bytes(12)),
            tag: base64_encode(random_bytes(16)),
            algorithm: SymmetricAlgorithm::AES256GCM,
        );

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($data, SymmetricAlgorithm::AES256GCM, $key)
            ->willReturn($encryptedData);

        $result = $this->anonymizer->pseudonymize($data, $keyId);

        $this->assertInstanceOf(PseudonymizedData::class, $result);
        $this->assertSame($keyId, $result->keyId);
        $this->assertSame(1, $result->keyVersion); // Default version
        $this->assertNotEmpty($result->pseudonym);
        $this->assertArrayHasKey('algorithm', $result->metadata);
    }

    public function test_pseudonymize_throws_on_key_not_found(): void
    {
        $keyId = 'nonexistent-key';

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willThrowException(new \RuntimeException('Key not found'));

        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('Pseudonymization failed');

        $this->anonymizer->pseudonymize('data', $keyId);
    }

    public function test_pseudonymize_throws_on_encryption_error(): void
    {
        $keyId = 'valid-key';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->method('retrieve')
            ->willReturn($key);

        $this->encryptor
            ->method('encrypt')
            ->willThrowException(new \RuntimeException('Encryption failed'));

        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('Pseudonymization failed');

        $this->anonymizer->pseudonymize('data', $keyId);
    }

    // =====================================================
    // DE-PSEUDONYMIZE TESTS
    // =====================================================

    public function test_de_pseudonymize_decrypts_data(): void
    {
        $originalData = 'john@example.com';
        $keyId = 'customer-key-001';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        // Create serialized encrypted data (base64 of JSON)
        $encryptedArray = [
            'ciphertext' => base64_encode('encrypted'),
            'iv' => base64_encode(random_bytes(12)),
            'tag' => base64_encode(random_bytes(16)),
            'algorithm' => 'aes-256-gcm',
        ];
        $serializedPseudonym = base64_encode(json_encode($encryptedArray));

        $pseudonymized = new PseudonymizedData(
            pseudonym: $serializedPseudonym,
            keyId: $keyId,
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->willReturn($originalData);

        $result = $this->anonymizer->dePseudonymize($pseudonymized);

        $this->assertSame($originalData, $result);
    }

    public function test_de_pseudonymize_throws_on_key_not_found(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: base64_encode('{"ciphertext":"test","iv":"test","tag":"test","algorithm":"aes-256-gcm"}'),
            keyId: 'nonexistent-key',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->method('retrieve')
            ->willThrowException(new \RuntimeException('Key not found'));

        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('De-pseudonymization failed');

        $this->anonymizer->dePseudonymize($pseudonymized);
    }

    public function test_de_pseudonymize_throws_on_invalid_base64(): void
    {
        $keyId = 'valid-key';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->method('retrieve')
            ->willReturn($key);

        $pseudonymized = new PseudonymizedData(
            pseudonym: '!!!invalid_base64!!!',
            keyId: $keyId,
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('De-pseudonymization failed');

        $this->anonymizer->dePseudonymize($pseudonymized);
    }

    // =====================================================
    // GENERATE PSEUDONYM TESTS
    // =====================================================

    public function test_generate_pseudonym_creates_consistent_hmac(): void
    {
        $data = 'customer@example.com';
        $context = 'email-mapping';
        $keyId = 'pseudonym-key-001';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $result1 = $this->anonymizer->generatePseudonym($data, $context, $keyId);
        $result2 = $this->anonymizer->generatePseudonym($data, $context, $keyId);

        // Same input + context + key = same pseudonym
        $this->assertSame($result1, $result2);
        
        // HMAC-SHA256 output is 64 hex chars
        $this->assertSame(64, strlen($result1));
    }

    public function test_generate_pseudonym_different_context_produces_different_result(): void
    {
        $data = 'customer@example.com';
        $keyId = 'pseudonym-key-001';
        $keyMaterial = base64_encode(random_bytes(32));

        $key = new EncryptionKey(
            key: $keyMaterial,
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->keyStorage
            ->method('retrieve')
            ->willReturn($key);

        $result1 = $this->anonymizer->generatePseudonym($data, 'context-a', $keyId);
        $result2 = $this->anonymizer->generatePseudonym($data, 'context-b', $keyId);

        $this->assertNotSame($result1, $result2);
    }

    public function test_generate_pseudonym_throws_on_key_not_found(): void
    {
        $this->keyStorage
            ->method('retrieve')
            ->willThrowException(new \RuntimeException('Key not found'));

        $this->expectException(AnonymizationException::class);
        $this->expectExceptionMessage('Pseudonymization failed');

        $this->anonymizer->generatePseudonym('data', 'context', 'nonexistent-key');
    }

    // =====================================================
    // VERIFY ANONYMIZED TESTS
    // =====================================================

    public function test_verify_anonymized_returns_true_for_matching_hash(): void
    {
        $data = 'test@example.com';
        $hashValue = 'a1b2c3d4e5f6789012345678901234567890abcdef';

        $this->hasher
            ->method('hash')
            ->with($data, HashAlgorithm::SHA256)
            ->willReturn(new HashResult(
                hash: $hashValue,
                algorithm: HashAlgorithm::SHA256,
            ));

        $anonymized = new AnonymizedData(
            anonymizedValue: $hashValue,
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $result = $this->anonymizer->verifyAnonymized($data, $anonymized);

        $this->assertTrue($result);
    }

    public function test_verify_anonymized_returns_false_for_non_matching_hash(): void
    {
        $data = 'test@example.com';

        $this->hasher
            ->method('hash')
            ->willReturn(new HashResult(
                hash: 'different_hash_value',
                algorithm: HashAlgorithm::SHA256,
            ));

        $anonymized = new AnonymizedData(
            anonymizedValue: 'original_hash_value',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $result = $this->anonymizer->verifyAnonymized($data, $anonymized);

        $this->assertFalse($result);
    }

    public function test_verify_anonymized_returns_false_for_non_deterministic_methods(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: 'salted_value',
            method: AnonymizationMethod::SALTED_HASH,
            anonymizedAt: new DateTimeImmutable(),
            salt: 'some_salt',
        );

        $result = $this->anonymizer->verifyAnonymized('data', $anonymized);

        $this->assertFalse($result);
    }

    public function test_verify_anonymized_returns_false_for_suppression(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: '[SUPPRESSED]',
            method: AnonymizationMethod::SUPPRESSION,
            anonymizedAt: new DateTimeImmutable(),
        );

        $result = $this->anonymizer->verifyAnonymized('any data', $anonymized);

        $this->assertFalse($result);
    }

    public function test_verify_anonymized_returns_false_on_exception(): void
    {
        $this->hasher
            ->method('hash')
            ->willThrowException(new \RuntimeException('Hash error'));

        $anonymized = new AnonymizedData(
            anonymizedValue: 'some_hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $result = $this->anonymizer->verifyAnonymized('data', $anonymized);

        $this->assertFalse($result);
    }

    // =====================================================
    // DEFAULT METHOD TESTS
    // =====================================================

    public function test_anonymize_uses_salted_hash_by_default(): void
    {
        $this->hasher
            ->method('hash')
            ->willReturn(new HashResult(
                hash: 'test_hash',
                algorithm: HashAlgorithm::SHA256,
            ));

        $result = $this->anonymizer->anonymize('sensitive_data');

        $this->assertSame(AnonymizationMethod::SALTED_HASH, $result->method);
    }

    // =====================================================
    // LOGGING TESTS
    // =====================================================

    public function test_anonymize_logs_operations(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        
        $logger
            ->expects($this->atLeastOnce())
            ->method('debug');
        
        $logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $anonymizer = new Anonymizer(
            $this->hasher,
            $this->encryptor,
            $this->keyStorage,
            $logger,
        );

        $this->hasher
            ->method('hash')
            ->willReturn(new HashResult('hash', HashAlgorithm::SHA256));

        $anonymizer->anonymize('data', AnonymizationMethod::HASH_BASED);
    }

    // =====================================================
    // CLASS STRUCTURE TESTS
    // =====================================================

    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(Anonymizer::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_implements_anonymizer_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\Crypto\Contracts\AnonymizerInterface::class,
            $this->anonymizer
        );
    }
}
