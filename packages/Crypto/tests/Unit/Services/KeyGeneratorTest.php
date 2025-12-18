<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\FeatureNotImplementedException;
use Nexus\Crypto\Exceptions\UnsupportedAlgorithmException;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\KeyPair;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Crypto\Services\KeyGenerator
 */
final class KeyGeneratorTest extends TestCase
{
    private KeyGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new KeyGenerator();
    }

    // =====================================================
    // SYMMETRIC KEY GENERATION TESTS
    // =====================================================

    public function test_generate_symmetric_key_aes256gcm(): void
    {
        $key = $this->generator->generateSymmetricKey(SymmetricAlgorithm::AES256GCM);
        
        $this->assertInstanceOf(EncryptionKey::class, $key);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $key->algorithm);
        $this->assertSame(32, strlen($key->getKeyBinary())); // 256 bits
        $this->assertNotNull($key->createdAt);
    }

    public function test_generate_symmetric_key_chacha20poly1305(): void
    {
        $key = $this->generator->generateSymmetricKey(SymmetricAlgorithm::CHACHA20POLY1305);
        
        $this->assertSame(SymmetricAlgorithm::CHACHA20POLY1305, $key->algorithm);
        $this->assertSame(32, strlen($key->getKeyBinary())); // 256 bits
    }

    public function test_generate_symmetric_key_aes256cbc(): void
    {
        $key = $this->generator->generateSymmetricKey(SymmetricAlgorithm::AES256CBC);
        
        $this->assertSame(SymmetricAlgorithm::AES256CBC, $key->algorithm);
        $this->assertSame(32, strlen($key->getKeyBinary())); // 256 bits
    }

    public function test_generate_symmetric_key_defaults_to_aes256gcm(): void
    {
        $key = $this->generator->generateSymmetricKey();
        
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $key->algorithm);
    }

    public function test_generate_symmetric_key_with_expiration(): void
    {
        $key = $this->generator->generateSymmetricKey(
            algorithm: SymmetricAlgorithm::AES256GCM,
            expirationDays: 30
        );
        
        $this->assertNotNull($key->expiresAt);
        
        $expectedExpiration = $key->createdAt->modify('+30 days');
        $this->assertEquals(
            $expectedExpiration->format('Y-m-d'),
            $key->expiresAt->format('Y-m-d')
        );
    }

    public function test_generate_symmetric_key_without_expiration(): void
    {
        $key = $this->generator->generateSymmetricKey(
            algorithm: SymmetricAlgorithm::AES256GCM,
            expirationDays: null
        );
        
        $this->assertNull($key->expiresAt);
    }

    public function test_generate_symmetric_key_produces_unique_keys(): void
    {
        $key1 = $this->generator->generateSymmetricKey(SymmetricAlgorithm::AES256GCM);
        $key2 = $this->generator->generateSymmetricKey(SymmetricAlgorithm::AES256GCM);
        
        $this->assertNotSame($key1->key, $key2->key);
        // Keys should be unique - key material is different
        $this->assertNotSame($key1->getKeyBinary(), $key2->getKeyBinary());
    }

    // =====================================================
    // KEY PAIR GENERATION TESTS
    // =====================================================

    public function test_generate_key_pair_ed25519(): void
    {
        $keyPair = $this->generator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        
        $this->assertInstanceOf(KeyPair::class, $keyPair);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $keyPair->algorithm);
        $this->assertNotEmpty($keyPair->publicKey);
        $this->assertNotEmpty($keyPair->privateKey);
        // Ed25519 public key is 32 bytes
        $this->assertSame(32, strlen($keyPair->getPublicKeyBinary()));
    }

    public function test_generate_key_pair_rsa2048(): void
    {
        $keyPair = $this->generator->generateKeyPair(AsymmetricAlgorithm::RSA2048);
        
        $this->assertSame(AsymmetricAlgorithm::RSA2048, $keyPair->algorithm);
        $this->assertNotEmpty($keyPair->publicKey);
        $this->assertNotEmpty($keyPair->privateKey);
    }

    public function test_generate_key_pair_rsa4096(): void
    {
        $keyPair = $this->generator->generateKeyPair(AsymmetricAlgorithm::RSA4096);
        
        $this->assertSame(AsymmetricAlgorithm::RSA4096, $keyPair->algorithm);
        $this->assertNotEmpty($keyPair->publicKey);
        $this->assertNotEmpty($keyPair->privateKey);
    }

    public function test_generate_key_pair_defaults_to_ed25519(): void
    {
        $keyPair = $this->generator->generateKeyPair();
        
        $this->assertSame(AsymmetricAlgorithm::ED25519, $keyPair->algorithm);
    }

    public function test_generate_key_pair_produces_unique_pairs(): void
    {
        $keyPair1 = $this->generator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $keyPair2 = $this->generator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        
        $this->assertNotSame($keyPair1->publicKey, $keyPair2->publicKey);
        $this->assertNotSame($keyPair1->privateKey, $keyPair2->privateKey);
    }

    public function test_generate_key_pair_throws_for_unimplemented_pqc(): void
    {
        $this->expectException(FeatureNotImplementedException::class);
        $this->expectExceptionMessage('Post-quantum');
        
        $this->generator->generateKeyPair(AsymmetricAlgorithm::KYBER768);
    }

    public function test_generate_key_pair_throws_for_hmac(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);
        
        $this->generator->generateKeyPair(AsymmetricAlgorithm::HMACSHA256);
    }

    public function test_generate_key_pair_throws_for_ecdsa(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);
        
        $this->generator->generateKeyPair(AsymmetricAlgorithm::ECDSAP256);
    }

    // =====================================================
    // RANDOM BYTES TESTS
    // =====================================================

    public function test_generate_random_bytes(): void
    {
        $randomBytes = $this->generator->generateRandomBytes(32);
        
        $this->assertNotEmpty($randomBytes);
        // Result is base64 encoded, so decoded should be 32 bytes
        $decoded = base64_decode($randomBytes, true);
        $this->assertSame(32, strlen($decoded));
    }

    public function test_generate_random_bytes_various_lengths(): void
    {
        foreach ([16, 32, 64, 128, 256] as $length) {
            $randomBytes = $this->generator->generateRandomBytes($length);
            $decoded = base64_decode($randomBytes, true);
            
            $this->assertSame($length, strlen($decoded), "Failed for length: $length");
        }
    }

    public function test_generate_random_bytes_produces_unique_values(): void
    {
        $bytes1 = $this->generator->generateRandomBytes(32);
        $bytes2 = $this->generator->generateRandomBytes(32);
        
        $this->assertNotSame($bytes1, $bytes2);
    }

    public function test_generate_random_bytes_throws_for_zero_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('positive');
        
        $this->generator->generateRandomBytes(0);
    }

    public function test_generate_random_bytes_throws_for_negative_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('positive');
        
        $this->generator->generateRandomBytes(-1);
    }

    public function test_generate_random_bytes_throws_for_excessive_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('1MB');
        
        $this->generator->generateRandomBytes(1048577); // 1MB + 1
    }

    public function test_generate_random_bytes_allows_max_length(): void
    {
        // This should not throw - it's exactly 1MB
        $randomBytes = $this->generator->generateRandomBytes(1048576);
        
        $decoded = base64_decode($randomBytes, true);
        $this->assertSame(1048576, strlen($decoded));
    }

    // =====================================================
    // INTERFACE COMPLIANCE TESTS
    // =====================================================

    public function test_implements_key_generator_interface(): void
    {
        $this->assertInstanceOf(KeyGeneratorInterface::class, $this->generator);
    }

    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(KeyGenerator::class);
        
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
