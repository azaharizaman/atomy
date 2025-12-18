<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Exceptions\FeatureNotImplementedException;
use Nexus\Crypto\Exceptions\SignatureException;
use Nexus\Crypto\Exceptions\UnsupportedAlgorithmException;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\Services\SodiumSigner;
use Nexus\Crypto\ValueObjects\SignedData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SodiumSigner::class)]
final class SodiumSignerTest extends TestCase
{
    private SodiumSigner $signer;
    private KeyGenerator $keyGenerator;

    protected function setUp(): void
    {
        $this->signer = new SodiumSigner();
        $this->keyGenerator = new KeyGenerator();
    }

    // ========================================
    // Ed25519 Signing Tests
    // ========================================

    #[Test]
    public function sign_with_ed25519_returns_signed_data(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'Hello, World!';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);

        $this->assertInstanceOf(SignedData::class, $signed);
        $this->assertSame($data, $signed->data);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $signed->algorithm);
        $this->assertNotEmpty($signed->signature);
    }

    #[Test]
    public function verify_ed25519_signature_with_correct_key_returns_true(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'This is a test message';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function verify_ed25519_signature_with_wrong_key_returns_false(): void
    {
        $keyPair1 = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $keyPair2 = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'This is a test message';

        $signed = $this->signer->sign($data, $keyPair1->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair2->publicKey);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_ed25519_signature_with_tampered_data_returns_false(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'Original message';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);

        // Tamper with data
        $tamperedSigned = new SignedData(
            data: 'Tampered message',
            signature: $signed->signature,
            algorithm: $signed->algorithm,
            publicKey: $signed->publicKey,
        );

        $isValid = $this->signer->verify($tamperedSigned, $keyPair->publicKey);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_ed25519_with_invalid_signature_format_returns_false(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'Test message';

        $signed = new SignedData(
            data: $data,
            signature: 'not-valid-base64!@#$',
            algorithm: AsymmetricAlgorithm::ED25519,
            publicKey: null,
        );

        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_ed25519_with_invalid_public_key_format_returns_false(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'Test message';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, 'invalid-key');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_ed25519_with_wrong_length_public_key_returns_false(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = 'Test message';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        
        // Create key with wrong length
        $wrongLengthKey = base64_encode(random_bytes(16));
        $isValid = $this->signer->verify($signed, $wrongLengthKey);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function sign_ed25519_with_invalid_private_key_throws_exception(): void
    {
        $this->expectException(SignatureException::class);

        $this->signer->sign('test', 'invalid-private-key', AsymmetricAlgorithm::ED25519);
    }

    #[Test]
    public function sign_ed25519_with_wrong_length_private_key_throws_exception(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Invalid Ed25519 private key format');

        $wrongLengthKey = base64_encode(random_bytes(16));
        $this->signer->sign('test', $wrongLengthKey, AsymmetricAlgorithm::ED25519);
    }

    // ========================================
    // HMAC-SHA256 Signing Tests
    // ========================================

    #[Test]
    public function sign_with_hmac_sha256_returns_signed_data(): void
    {
        $secret = 'my-secret-key';
        $data = 'Hello, World!';

        $signed = $this->signer->sign($data, $secret, AsymmetricAlgorithm::HMACSHA256);

        $this->assertInstanceOf(SignedData::class, $signed);
        $this->assertSame($data, $signed->data);
        $this->assertSame(AsymmetricAlgorithm::HMACSHA256, $signed->algorithm);
        $this->assertNotEmpty($signed->signature);
    }

    #[Test]
    public function hmac_returns_hash_for_data(): void
    {
        $secret = 'my-secret-key';
        $data = 'Hello, World!';

        $hmac = $this->signer->hmac($data, $secret);

        $this->assertNotEmpty($hmac);
        $this->assertSame(64, strlen($hmac)); // SHA256 hex = 64 chars
    }

    #[Test]
    public function hmac_is_deterministic(): void
    {
        $secret = 'my-secret-key';
        $data = 'Hello, World!';

        $hmac1 = $this->signer->hmac($data, $secret);
        $hmac2 = $this->signer->hmac($data, $secret);

        $this->assertSame($hmac1, $hmac2);
    }

    #[Test]
    public function hmac_differs_with_different_data(): void
    {
        $secret = 'my-secret-key';

        $hmac1 = $this->signer->hmac('data1', $secret);
        $hmac2 = $this->signer->hmac('data2', $secret);

        $this->assertNotSame($hmac1, $hmac2);
    }

    #[Test]
    public function hmac_differs_with_different_secrets(): void
    {
        $data = 'Hello, World!';

        $hmac1 = $this->signer->hmac($data, 'secret1');
        $hmac2 = $this->signer->hmac($data, 'secret2');

        $this->assertNotSame($hmac1, $hmac2);
    }

    #[Test]
    public function hmac_with_unsupported_algorithm_throws_exception(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);

        $this->signer->hmac('data', 'secret', AsymmetricAlgorithm::ED25519);
    }

    #[Test]
    public function verify_hmac_returns_true_for_valid_signature(): void
    {
        $secret = 'my-secret-key';
        $data = 'Hello, World!';

        $signature = $this->signer->hmac($data, $secret);
        $isValid = $this->signer->verifyHmac($data, $signature, $secret);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function verify_hmac_returns_false_for_invalid_signature(): void
    {
        $secret = 'my-secret-key';
        $data = 'Hello, World!';

        $isValid = $this->signer->verifyHmac($data, 'invalid-signature', $secret);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_hmac_returns_false_for_wrong_secret(): void
    {
        $data = 'Hello, World!';

        $signature = $this->signer->hmac($data, 'secret1');
        $isValid = $this->signer->verifyHmac($data, $signature, 'secret2');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function verify_hmac_returns_false_for_modified_data(): void
    {
        $secret = 'my-secret-key';

        $signature = $this->signer->hmac('original data', $secret);
        $isValid = $this->signer->verifyHmac('modified data', $signature, $secret);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function sign_and_verify_hmac_roundtrip(): void
    {
        $secret = 'my-secret-key';
        $data = 'Test message for HMAC';

        $signed = $this->signer->sign($data, $secret, AsymmetricAlgorithm::HMACSHA256);
        $isValid = $this->signer->verify($signed, $secret);

        $this->assertTrue($isValid);
    }

    // ========================================
    // Unsupported Algorithm Tests
    // ========================================

    #[Test]
    public function sign_with_rsa2048_throws_exception(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);

        $this->signer->sign('test', 'key', AsymmetricAlgorithm::RSA2048);
    }

    #[Test]
    public function sign_with_rsa4096_throws_exception(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);

        $this->signer->sign('test', 'key', AsymmetricAlgorithm::RSA4096);
    }

    #[Test]
    public function sign_with_ecdsa_throws_exception(): void
    {
        $this->expectException(UnsupportedAlgorithmException::class);

        $this->signer->sign('test', 'key', AsymmetricAlgorithm::ECDSAP256);
    }

    #[Test]
    public function sign_with_pqc_throws_feature_not_implemented_exception(): void
    {
        $this->expectException(FeatureNotImplementedException::class);

        $this->signer->sign('test', 'key', AsymmetricAlgorithm::DILITHIUM3);
    }

    #[Test]
    public function verify_with_pqc_throws_feature_not_implemented_exception(): void
    {
        $this->expectException(FeatureNotImplementedException::class);

        $signed = new SignedData(
            data: 'test',
            signature: 'sig',
            algorithm: AsymmetricAlgorithm::DILITHIUM3,
            publicKey: null,
        );

        $this->signer->verify($signed, 'key');
    }

    // ========================================
    // Edge Case Tests
    // ========================================

    #[Test]
    public function sign_and_verify_empty_string(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);

        $signed = $this->signer->sign('', $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function sign_and_verify_unicode_data(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = '日本語テスト 🔐 مرحبا';

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function sign_and_verify_binary_data(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = random_bytes(1024);

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function sign_and_verify_large_data(): void
    {
        $keyPair = $this->keyGenerator->generateKeyPair(AsymmetricAlgorithm::ED25519);
        $data = str_repeat('X', 1000000); // 1MB

        $signed = $this->signer->sign($data, $keyPair->privateKey, AsymmetricAlgorithm::ED25519);
        $isValid = $this->signer->verify($signed, $keyPair->publicKey);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function hmac_empty_string(): void
    {
        $hmac = $this->signer->hmac('', 'secret');

        $this->assertNotEmpty($hmac);
        $this->assertSame(64, strlen($hmac));
    }

    #[Test]
    public function hmac_with_empty_secret(): void
    {
        $hmac = $this->signer->hmac('data', '');

        $this->assertNotEmpty($hmac);
        $this->assertSame(64, strlen($hmac));
    }

    // ========================================
    // Data Provider Tests
    // ========================================

    #[Test]
    #[DataProvider('signatureAlgorithmProvider')]
    public function sign_and_verify_with_various_algorithms(AsymmetricAlgorithm $algorithm, bool $isSupported): void
    {
        if (!$isSupported) {
            $this->expectException(\Throwable::class);
        }

        if ($algorithm === AsymmetricAlgorithm::HMACSHA256) {
            $key = 'hmac-secret-key';
            $verifyKey = $key;
        } elseif ($algorithm === AsymmetricAlgorithm::ED25519) {
            $keyPair = $this->keyGenerator->generateKeyPair($algorithm);
            $key = $keyPair->privateKey;
            $verifyKey = $keyPair->publicKey;
        } else {
            $key = 'dummy-key';
            $verifyKey = $key;
        }

        $signed = $this->signer->sign('test data', $key, $algorithm);
        
        if ($isSupported) {
            $this->assertTrue($this->signer->verify($signed, $verifyKey));
        }
    }

    /**
     * @return array<string, array{AsymmetricAlgorithm, bool}>
     */
    public static function signatureAlgorithmProvider(): array
    {
        return [
            'ED25519' => [AsymmetricAlgorithm::ED25519, true],
            'HMACSHA256' => [AsymmetricAlgorithm::HMACSHA256, true],
            'RSA2048' => [AsymmetricAlgorithm::RSA2048, false],
            'RSA4096' => [AsymmetricAlgorithm::RSA4096, false],
            'ECDSAP256' => [AsymmetricAlgorithm::ECDSAP256, false],
        ];
    }
}
