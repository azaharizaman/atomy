<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\DecryptionException;
use Nexus\Crypto\Exceptions\EncryptionException;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\Services\SodiumEncryptor;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Crypto\Services\SodiumEncryptor
 */
final class SodiumEncryptorTest extends TestCase
{
    private SodiumEncryptor $encryptor;
    private KeyGenerator $keyGenerator;

    protected function setUp(): void
    {
        $this->encryptor = new SodiumEncryptor();
        $this->keyGenerator = new KeyGenerator();
    }

    /**
     * Helper to generate encryption keys
     */
    private function generateKey(SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM): EncryptionKey
    {
        return $this->keyGenerator->generateSymmetricKey($algorithm);
    }

    // =====================================================
    // ENCRYPTION TESTS
    // =====================================================

    public function test_encrypt_with_aes256gcm(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);

        $this->assertInstanceOf(EncryptedData::class, $encrypted);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $encrypted->algorithm);
        $this->assertNotEmpty($encrypted->ciphertext);
        $this->assertNotEmpty($encrypted->iv);
        $this->assertNotEmpty($encrypted->tag);
    }

    public function test_encrypt_with_chacha20poly1305(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::CHACHA20POLY1305);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::CHACHA20POLY1305, $key);

        $this->assertInstanceOf(EncryptedData::class, $encrypted);
        $this->assertSame(SymmetricAlgorithm::CHACHA20POLY1305, $encrypted->algorithm);
        $this->assertNotEmpty($encrypted->ciphertext);
        $this->assertNotEmpty($encrypted->iv);
    }

    public function test_encrypt_with_aes256cbc(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256CBC);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256CBC, $key);

        $this->assertInstanceOf(EncryptedData::class, $encrypted);
        $this->assertSame(SymmetricAlgorithm::AES256CBC, $encrypted->algorithm);
        $this->assertNotEmpty($encrypted->ciphertext);
        $this->assertNotEmpty($encrypted->iv);
    }

    public function test_encrypt_throws_without_key(): void
    {
        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('required');

        $this->encryptor->encrypt('plaintext');
    }

    public function test_encrypt_throws_with_invalid_key_length(): void
    {
        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('Invalid key length');

        // Create a key with wrong length (16 bytes instead of 32)
        $shortKey = new EncryptionKey(
            key: base64_encode(random_bytes(16)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new \DateTimeImmutable(),
        );

        $this->encryptor->encrypt('plaintext', SymmetricAlgorithm::AES256GCM, $shortKey);
    }

    public function test_encrypt_generates_unique_iv_each_time(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = 'Same plaintext';

        $encrypted1 = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $encrypted2 = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);

        // IVs should be different (nonce reuse prevention)
        $this->assertNotSame($encrypted1->iv, $encrypted2->iv);
        // Ciphertexts should also be different due to different IVs
        $this->assertNotSame($encrypted1->ciphertext, $encrypted2->ciphertext);
    }

    // =====================================================
    // DECRYPTION TESTS
    // =====================================================

    public function test_decrypt_with_aes256gcm(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_decrypt_with_chacha20poly1305(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::CHACHA20POLY1305);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::CHACHA20POLY1305, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_decrypt_with_aes256cbc(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256CBC);
        $plaintext = 'Hello, World!';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256CBC, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_decrypt_throws_without_key(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $encrypted = $this->encryptor->encrypt('plaintext', SymmetricAlgorithm::AES256GCM, $key);

        $this->expectException(DecryptionException::class);
        $this->expectExceptionMessage('required');

        $this->encryptor->decrypt($encrypted);
    }

    public function test_decrypt_throws_with_wrong_key(): void
    {
        $key1 = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $key2 = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        
        $encrypted = $this->encryptor->encrypt('plaintext', SymmetricAlgorithm::AES256GCM, $key1);

        $this->expectException(DecryptionException::class);

        $this->encryptor->decrypt($encrypted, $key2);
    }

    public function test_decrypt_throws_with_tampered_ciphertext(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $encrypted = $this->encryptor->encrypt('plaintext', SymmetricAlgorithm::AES256GCM, $key);

        // Tamper with the ciphertext
        $tampered = new EncryptedData(
            ciphertext: base64_encode('tampered' . random_bytes(32)),
            iv: $encrypted->iv,
            tag: $encrypted->tag,
            algorithm: $encrypted->algorithm,
        );

        $this->expectException(DecryptionException::class);

        $this->encryptor->decrypt($tampered, $key);
    }

    // =====================================================
    // ROUNDTRIP TESTS
    // =====================================================

    public function test_encrypt_decrypt_roundtrip_with_empty_string(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = '';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypt_decrypt_roundtrip_with_unicode(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = '你好世界 🌍 مرحبا';

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypt_decrypt_roundtrip_with_large_data(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = str_repeat('A', 1024 * 1024); // 1MB

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypt_decrypt_roundtrip_with_binary_data(): void
    {
        $key = $this->generateKey(SymmetricAlgorithm::AES256GCM);
        $plaintext = random_bytes(256);

        $encrypted = $this->encryptor->encrypt($plaintext, SymmetricAlgorithm::AES256GCM, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    // =====================================================
    // ALGORITHM VERIFICATION TESTS
    // =====================================================

    /**
     * @dataProvider algorithmProvider
     */
    public function test_all_algorithms_work_correctly(SymmetricAlgorithm $algorithm): void
    {
        $key = $this->generateKey($algorithm);
        $plaintext = 'Test data for ' . $algorithm->value;

        $encrypted = $this->encryptor->encrypt($plaintext, $algorithm, $key);
        $decrypted = $this->encryptor->decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
        $this->assertSame($algorithm, $encrypted->algorithm);
    }

    public static function algorithmProvider(): array
    {
        return [
            'AES-256-GCM' => [SymmetricAlgorithm::AES256GCM],
            'ChaCha20-Poly1305' => [SymmetricAlgorithm::CHACHA20POLY1305],
            'AES-256-CBC' => [SymmetricAlgorithm::AES256CBC],
        ];
    }

    // =====================================================
    // INTERFACE COMPLIANCE TESTS
    // =====================================================

    public function test_implements_symmetric_encryptor_interface(): void
    {
        $this->assertInstanceOf(SymmetricEncryptorInterface::class, $this->encryptor);
    }

    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(SodiumEncryptor::class);
        
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}

