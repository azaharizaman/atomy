<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\Crypto\ValueObjects\KeyPair;
use Nexus\Crypto\ValueObjects\SignedData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EncryptedData::class)]
#[CoversClass(EncryptionKey::class)]
#[CoversClass(HashResult::class)]
#[CoversClass(KeyPair::class)]
#[CoversClass(SignedData::class)]
final class ValueObjectsTest extends TestCase
{
    // ========================================
    // EncryptedData Tests
    // ========================================

    #[Test]
    public function encrypted_data_can_be_created(): void
    {
        $encrypted = new EncryptedData(
            ciphertext: 'dGVzdA==',
            iv: 'aXY=',
            tag: 'dGFn',
            algorithm: SymmetricAlgorithm::AES256GCM,
            metadata: ['key_id' => 'key-1'],
        );

        $this->assertSame('dGVzdA==', $encrypted->ciphertext);
        $this->assertSame('aXY=', $encrypted->iv);
        $this->assertSame('dGFn', $encrypted->tag);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $encrypted->algorithm);
        $this->assertSame(['key_id' => 'key-1'], $encrypted->metadata);
    }

    #[Test]
    public function encrypted_data_to_array(): void
    {
        $encrypted = new EncryptedData(
            ciphertext: 'dGVzdA==',
            iv: 'aXY=',
            tag: 'dGFn',
            algorithm: SymmetricAlgorithm::AES256GCM,
            metadata: ['key_id' => 'key-1'],
        );

        $array = $encrypted->toArray();

        $this->assertSame('dGVzdA==', $array['ciphertext']);
        $this->assertSame('aXY=', $array['iv']);
        $this->assertSame('dGFn', $array['tag']);
        $this->assertSame('aes-256-gcm', $array['algorithm']);
        $this->assertSame(['key_id' => 'key-1'], $array['metadata']);
    }

    #[Test]
    public function encrypted_data_from_array(): void
    {
        $data = [
            'ciphertext' => 'dGVzdA==',
            'iv' => 'aXY=',
            'tag' => 'dGFn',
            'algorithm' => 'aes-256-gcm',
            'metadata' => ['key_id' => 'key-1'],
        ];

        $encrypted = EncryptedData::fromArray($data);

        $this->assertSame('dGVzdA==', $encrypted->ciphertext);
        $this->assertSame('aXY=', $encrypted->iv);
        $this->assertSame('dGFn', $encrypted->tag);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $encrypted->algorithm);
        $this->assertSame(['key_id' => 'key-1'], $encrypted->metadata);
    }

    #[Test]
    public function encrypted_data_from_array_with_defaults(): void
    {
        $data = [
            'ciphertext' => 'dGVzdA==',
            'iv' => 'aXY=',
            'algorithm' => 'aes-256-gcm',
        ];

        $encrypted = EncryptedData::fromArray($data);

        $this->assertSame('', $encrypted->tag);
        $this->assertSame([], $encrypted->metadata);
    }

    #[Test]
    public function encrypted_data_to_json(): void
    {
        $encrypted = new EncryptedData(
            ciphertext: 'dGVzdA==',
            iv: 'aXY=',
            tag: 'dGFn',
            algorithm: SymmetricAlgorithm::AES256GCM,
        );

        $json = $encrypted->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('dGVzdA==', $decoded['ciphertext']);
        $this->assertSame('aes-256-gcm', $decoded['algorithm']);
    }

    #[Test]
    public function encrypted_data_from_json(): void
    {
        $json = '{"ciphertext":"dGVzdA==","iv":"aXY=","tag":"dGFn","algorithm":"aes-256-gcm","metadata":{}}';

        $encrypted = EncryptedData::fromJson($json);

        $this->assertSame('dGVzdA==', $encrypted->ciphertext);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $encrypted->algorithm);
    }

    #[Test]
    public function encrypted_data_roundtrip_json(): void
    {
        $original = new EncryptedData(
            ciphertext: 'abc123',
            iv: 'iv123',
            tag: 'tag456',
            algorithm: SymmetricAlgorithm::CHACHA20POLY1305,
            metadata: ['foo' => 'bar'],
        );

        $json = $original->toJson();
        $restored = EncryptedData::fromJson($json);

        $this->assertSame($original->ciphertext, $restored->ciphertext);
        $this->assertSame($original->iv, $restored->iv);
        $this->assertSame($original->tag, $restored->tag);
        $this->assertSame($original->algorithm, $restored->algorithm);
    }

    // ========================================
    // EncryptionKey Tests
    // ========================================

    #[Test]
    public function encryption_key_can_be_created(): void
    {
        $now = new DateTimeImmutable();
        $expires = $now->modify('+30 days');

        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: $now,
            expiresAt: $expires,
        );

        $this->assertNotEmpty($key->key);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $key->algorithm);
        $this->assertSame($now, $key->createdAt);
        $this->assertSame($expires, $key->expiresAt);
    }

    #[Test]
    public function encryption_key_is_not_expired_before_expiry(): void
    {
        $now = new DateTimeImmutable();
        $expires = $now->modify('+30 days');

        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: $now,
            expiresAt: $expires,
        );

        $this->assertFalse($key->isExpired($now));
        $this->assertFalse($key->isExpired($now->modify('+29 days')));
    }

    #[Test]
    public function encryption_key_is_expired_after_expiry(): void
    {
        $now = new DateTimeImmutable();
        $expires = $now->modify('+30 days');

        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: $now,
            expiresAt: $expires,
        );

        $this->assertTrue($key->isExpired($expires));
        $this->assertTrue($key->isExpired($expires->modify('+1 day')));
    }

    #[Test]
    public function encryption_key_never_expires_when_null(): void
    {
        $now = new DateTimeImmutable();

        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: $now,
            expiresAt: null,
        );

        $this->assertFalse($key->isExpired($now));
        $this->assertFalse($key->isExpired($now->modify('+100 years')));
    }

    #[Test]
    public function encryption_key_get_key_binary(): void
    {
        $rawKey = random_bytes(32);
        $key = new EncryptionKey(
            key: base64_encode($rawKey),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertSame($rawKey, $key->getKeyBinary());
    }

    #[Test]
    public function encryption_key_get_key_binary_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $key = new EncryptionKey(
            key: 'not-valid-base64!@#$',
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
        );

        $key->getKeyBinary();
    }

    #[Test]
    public function encryption_key_to_array(): void
    {
        $now = new DateTimeImmutable('2024-01-15T12:00:00+00:00');
        $expires = new DateTimeImmutable('2024-02-15T12:00:00+00:00');

        $key = new EncryptionKey(
            key: 'abc123',
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: $now,
            expiresAt: $expires,
        );

        $array = $key->toArray();

        $this->assertSame('abc123', $array['key']);
        $this->assertSame('aes-256-gcm', $array['algorithm']);
        $this->assertSame('2024-01-15T12:00:00+00:00', $array['createdAt']);
        $this->assertSame('2024-02-15T12:00:00+00:00', $array['expiresAt']);
    }

    #[Test]
    public function encryption_key_from_array(): void
    {
        $data = [
            'key' => 'abc123',
            'algorithm' => 'aes-256-gcm',
            'createdAt' => '2024-01-15T12:00:00+00:00',
            'expiresAt' => '2024-02-15T12:00:00+00:00',
        ];

        $key = EncryptionKey::fromArray($data);

        $this->assertSame('abc123', $key->key);
        $this->assertSame(SymmetricAlgorithm::AES256GCM, $key->algorithm);
    }

    // ========================================
    // HashResult Tests
    // ========================================

    #[Test]
    public function hash_result_can_be_created(): void
    {
        $hashHex = hash('sha256', 'test');

        $result = new HashResult(
            hash: $hashHex,
            algorithm: HashAlgorithm::SHA256,
            salt: 'random-salt',
        );

        $this->assertSame($hashHex, $result->hash);
        $this->assertSame(HashAlgorithm::SHA256, $result->algorithm);
        $this->assertSame('random-salt', $result->salt);
    }

    #[Test]
    public function hash_result_to_array(): void
    {
        $result = new HashResult(
            hash: 'abc123',
            algorithm: HashAlgorithm::SHA256,
            salt: 'salt123',
        );

        $array = $result->toArray();

        $this->assertSame('abc123', $array['hash']);
        $this->assertSame('sha256', $array['algorithm']);
        $this->assertSame('salt123', $array['salt']);
    }

    #[Test]
    public function hash_result_from_array(): void
    {
        $data = [
            'hash' => 'abc123',
            'algorithm' => 'sha256',
            'salt' => 'salt123',
        ];

        $result = HashResult::fromArray($data);

        $this->assertSame('abc123', $result->hash);
        $this->assertSame(HashAlgorithm::SHA256, $result->algorithm);
        $this->assertSame('salt123', $result->salt);
    }

    #[Test]
    public function hash_result_from_array_without_salt(): void
    {
        $data = [
            'hash' => 'abc123',
            'algorithm' => 'sha256',
        ];

        $result = HashResult::fromArray($data);

        $this->assertNull($result->salt);
    }

    #[Test]
    public function hash_result_get_binary(): void
    {
        // Valid hex hash
        $hashHex = hash('sha256', 'test');
        $result = new HashResult(
            hash: $hashHex,
            algorithm: HashAlgorithm::SHA256,
        );

        $binary = $result->getBinary();

        $this->assertSame(hex2bin($hashHex), $binary);
    }

    #[Test]
    public function hash_result_get_binary_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $result = new HashResult(
            hash: 'not-valid-hex!@#$',
            algorithm: HashAlgorithm::SHA256,
        );

        $result->getBinary();
    }

    #[Test]
    public function hash_result_matches_returns_true_for_equal_hashes(): void
    {
        $hashHex = hash('sha256', 'test');
        $result = new HashResult(
            hash: $hashHex,
            algorithm: HashAlgorithm::SHA256,
        );

        $this->assertTrue($result->matches($hashHex));
    }

    #[Test]
    public function hash_result_matches_returns_false_for_different_hashes(): void
    {
        $hashHex = hash('sha256', 'test');
        $result = new HashResult(
            hash: $hashHex,
            algorithm: HashAlgorithm::SHA256,
        );

        $this->assertFalse($result->matches('different-hash'));
    }

    // ========================================
    // KeyPair Tests
    // ========================================

    #[Test]
    public function key_pair_can_be_created(): void
    {
        $keyPair = new KeyPair(
            publicKey: 'public-key-base64',
            privateKey: 'private-key-base64',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertSame('public-key-base64', $keyPair->publicKey);
        $this->assertSame('private-key-base64', $keyPair->privateKey);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $keyPair->algorithm);
    }

    #[Test]
    public function key_pair_is_not_quantum_resistant_for_ed25519(): void
    {
        $keyPair = new KeyPair(
            publicKey: 'public',
            privateKey: 'private',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertFalse($keyPair->isQuantumResistant());
    }

    #[Test]
    public function key_pair_is_quantum_resistant_for_dilithium(): void
    {
        $keyPair = new KeyPair(
            publicKey: 'public',
            privateKey: 'private',
            algorithm: AsymmetricAlgorithm::DILITHIUM3,
        );

        $this->assertTrue($keyPair->isQuantumResistant());
    }

    #[Test]
    public function key_pair_to_array(): void
    {
        $keyPair = new KeyPair(
            publicKey: 'public123',
            privateKey: 'private456',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $array = $keyPair->toArray();

        $this->assertSame('public123', $array['publicKey']);
        $this->assertSame('private456', $array['privateKey']);
        $this->assertSame('ed25519', $array['algorithm']);
    }

    #[Test]
    public function key_pair_from_array(): void
    {
        $data = [
            'publicKey' => 'public123',
            'privateKey' => 'private456',
            'algorithm' => 'ed25519',
        ];

        $keyPair = KeyPair::fromArray($data);

        $this->assertSame('public123', $keyPair->publicKey);
        $this->assertSame('private456', $keyPair->privateKey);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $keyPair->algorithm);
    }

    #[Test]
    public function key_pair_get_public_key_binary(): void
    {
        $publicKeyRaw = random_bytes(32);
        $keyPair = new KeyPair(
            publicKey: base64_encode($publicKeyRaw),
            privateKey: base64_encode(random_bytes(64)),
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertSame($publicKeyRaw, $keyPair->getPublicKeyBinary());
    }

    #[Test]
    public function key_pair_get_public_key_binary_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $keyPair = new KeyPair(
            publicKey: 'not-valid-base64!@#$',
            privateKey: base64_encode(random_bytes(64)),
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $keyPair->getPublicKeyBinary();
    }

    #[Test]
    public function key_pair_get_private_key_binary(): void
    {
        $privateKeyRaw = random_bytes(64);
        $keyPair = new KeyPair(
            publicKey: base64_encode(random_bytes(32)),
            privateKey: base64_encode($privateKeyRaw),
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertSame($privateKeyRaw, $keyPair->getPrivateKeyBinary());
    }

    #[Test]
    public function key_pair_get_private_key_binary_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $keyPair = new KeyPair(
            publicKey: base64_encode(random_bytes(32)),
            privateKey: 'not-valid-base64!@#$',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $keyPair->getPrivateKeyBinary();
    }

    #[Test]
    public function key_pair_export_public_key(): void
    {
        $keyPair = new KeyPair(
            publicKey: 'public123',
            privateKey: 'private456',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $exported = $keyPair->exportPublicKey();

        $this->assertSame('public123', $exported['publicKey']);
        $this->assertSame('ed25519', $exported['algorithm']);
        $this->assertArrayNotHasKey('privateKey', $exported);
    }

    // ========================================
    // SignedData Tests
    // ========================================

    #[Test]
    public function signed_data_can_be_created(): void
    {
        $signed = new SignedData(
            data: 'Hello, World!',
            signature: 'signature-base64',
            algorithm: AsymmetricAlgorithm::ED25519,
            publicKey: 'public-key-base64',
        );

        $this->assertSame('Hello, World!', $signed->data);
        $this->assertSame('signature-base64', $signed->signature);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $signed->algorithm);
        $this->assertSame('public-key-base64', $signed->publicKey);
    }

    #[Test]
    public function signed_data_is_not_quantum_resistant_for_ed25519(): void
    {
        $signed = new SignedData(
            data: 'test',
            signature: 'sig',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertFalse($signed->isQuantumResistant());
    }

    #[Test]
    public function signed_data_is_quantum_resistant_for_dilithium(): void
    {
        $signed = new SignedData(
            data: 'test',
            signature: 'sig',
            algorithm: AsymmetricAlgorithm::DILITHIUM3,
        );

        $this->assertTrue($signed->isQuantumResistant());
    }

    #[Test]
    public function signed_data_to_array(): void
    {
        $signed = new SignedData(
            data: 'Hello',
            signature: 'sig123',
            algorithm: AsymmetricAlgorithm::ED25519,
            publicKey: 'pub123',
        );

        $array = $signed->toArray();

        $this->assertSame('Hello', $array['data']);
        $this->assertSame('sig123', $array['signature']);
        $this->assertSame('ed25519', $array['algorithm']);
        $this->assertSame('pub123', $array['publicKey']);
    }

    #[Test]
    public function signed_data_from_array(): void
    {
        $data = [
            'data' => 'Hello',
            'signature' => 'sig123',
            'algorithm' => 'ed25519',
            'publicKey' => 'pub123',
        ];

        $signed = SignedData::fromArray($data);

        $this->assertSame('Hello', $signed->data);
        $this->assertSame('sig123', $signed->signature);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $signed->algorithm);
        $this->assertSame('pub123', $signed->publicKey);
    }

    #[Test]
    public function signed_data_from_array_without_public_key(): void
    {
        $data = [
            'data' => 'Hello',
            'signature' => 'sig123',
            'algorithm' => 'ed25519',
        ];

        $signed = SignedData::fromArray($data);

        $this->assertNull($signed->publicKey);
    }

    #[Test]
    public function signed_data_to_json(): void
    {
        $signed = new SignedData(
            data: 'Hello',
            signature: 'sig123',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $json = $signed->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('Hello', $decoded['data']);
        $this->assertSame('sig123', $decoded['signature']);
        $this->assertSame('ed25519', $decoded['algorithm']);
    }

    #[Test]
    public function signed_data_from_json(): void
    {
        $json = '{"data":"Hello","signature":"sig123","algorithm":"ed25519","publicKey":null}';

        $signed = SignedData::fromJson($json);

        $this->assertSame('Hello', $signed->data);
        $this->assertSame('sig123', $signed->signature);
        $this->assertSame(AsymmetricAlgorithm::ED25519, $signed->algorithm);
    }

    #[Test]
    public function signed_data_roundtrip_json(): void
    {
        $original = new SignedData(
            data: 'Test data',
            signature: 'sig-data',
            algorithm: AsymmetricAlgorithm::HMACSHA256,
            publicKey: 'pub-key',
        );

        $json = $original->toJson();
        $restored = SignedData::fromJson($json);

        $this->assertSame($original->data, $restored->data);
        $this->assertSame($original->signature, $restored->signature);
        $this->assertSame($original->algorithm, $restored->algorithm);
        $this->assertSame($original->publicKey, $restored->publicKey);
    }

    #[Test]
    public function signed_data_get_signature_binary(): void
    {
        $sigRaw = random_bytes(64);
        $signed = new SignedData(
            data: 'test',
            signature: base64_encode($sigRaw),
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->assertSame($sigRaw, $signed->getSignatureBinary());
    }

    #[Test]
    public function signed_data_get_signature_binary_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $signed = new SignedData(
            data: 'test',
            signature: 'not-valid-base64!@#$',
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $signed->getSignatureBinary();
    }
}
