<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Crypto\Contracts\AnonymizerInterface;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\DataMaskerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\MaskingPattern;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\Crypto\ValueObjects\KeyPair;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use Nexus\Crypto\ValueObjects\SignedData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for CryptoManager facade
 */
final class CryptoManagerTest extends TestCase
{
    private HasherInterface&MockObject $hasher;
    private SymmetricEncryptorInterface&MockObject $encryptor;
    private AsymmetricSignerInterface&MockObject $signer;
    private KeyGeneratorInterface&MockObject $keyGenerator;
    private KeyStorageInterface&MockObject $keyStorage;
    private AnonymizerInterface&MockObject $anonymizer;
    private DataMaskerInterface&MockObject $dataMasker;
    private CryptoManager $manager;

    protected function setUp(): void
    {
        $this->hasher = $this->createMock(HasherInterface::class);
        $this->encryptor = $this->createMock(SymmetricEncryptorInterface::class);
        $this->signer = $this->createMock(AsymmetricSignerInterface::class);
        $this->keyGenerator = $this->createMock(KeyGeneratorInterface::class);
        $this->keyStorage = $this->createMock(KeyStorageInterface::class);
        $this->anonymizer = $this->createMock(AnonymizerInterface::class);
        $this->dataMasker = $this->createMock(DataMaskerInterface::class);

        $this->manager = new CryptoManager(
            hasher: $this->hasher,
            encryptor: $this->encryptor,
            signer: $this->signer,
            keyGenerator: $this->keyGenerator,
            keyStorage: $this->keyStorage,
            logger: new NullLogger(),
            anonymizer: $this->anonymizer,
            dataMasker: $this->dataMasker,
        );
    }

    // =====================================================
    // HASHING TESTS
    // =====================================================

    #[Test]
    public function hash_delegates_to_hasher(): void
    {
        $data = 'test data';
        $algorithm = HashAlgorithm::SHA256;
        $expected = new HashResult(
            hash: hash('sha256', $data),
            algorithm: $algorithm,
        );

        $this->hasher
            ->expects($this->once())
            ->method('hash')
            ->with($data, $algorithm)
            ->willReturn($expected);

        $result = $this->manager->hash($data, $algorithm);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function verify_hash_delegates_to_hasher(): void
    {
        $data = 'test data';
        $hashResult = new HashResult(
            hash: hash('sha256', $data),
            algorithm: HashAlgorithm::SHA256,
        );

        $this->hasher
            ->expects($this->once())
            ->method('verify')
            ->with($data, $hashResult)
            ->willReturn(true);

        $result = $this->manager->verifyHash($data, $hashResult);

        $this->assertTrue($result);
    }

    // =====================================================
    // ENCRYPTION TESTS
    // =====================================================

    #[Test]
    public function encrypt_delegates_to_encryptor(): void
    {
        $plaintext = 'secret message';
        $algorithm = SymmetricAlgorithm::AES256GCM;
        $encrypted = new EncryptedData(
            ciphertext: base64_encode('ciphertext'),
            iv: base64_encode('iv'),
            tag: base64_encode('tag'),
            algorithm: $algorithm,
        );

        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($plaintext, $algorithm)
            ->willReturn($encrypted);

        $result = $this->manager->encrypt($plaintext, $algorithm);

        $this->assertSame($encrypted, $result);
    }

    #[Test]
    public function decrypt_delegates_to_encryptor(): void
    {
        $encrypted = new EncryptedData(
            ciphertext: base64_encode('ciphertext'),
            iv: base64_encode('iv'),
            tag: base64_encode('tag'),
            algorithm: SymmetricAlgorithm::AES256GCM,
        );
        $expected = 'secret message';

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with($encrypted)
            ->willReturn($expected);

        $result = $this->manager->decrypt($encrypted);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function encrypt_with_key_retrieves_and_uses_stored_key(): void
    {
        $plaintext = 'secret message';
        $keyId = 'test-key-001';
        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new \DateTimeImmutable(),
        );
        $encrypted = new EncryptedData(
            ciphertext: base64_encode('ciphertext'),
            iv: base64_encode('iv'),
            tag: base64_encode('tag'),
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
            ->with($plaintext, $key->algorithm, $key)
            ->willReturn($encrypted);

        $result = $this->manager->encryptWithKey($plaintext, $keyId);

        $this->assertSame($encrypted->ciphertext, $result->ciphertext);
        $this->assertSame($keyId, $result->metadata['keyId']);
    }

    #[Test]
    public function decrypt_with_key_retrieves_and_uses_stored_key(): void
    {
        $keyId = 'test-key-001';
        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new \DateTimeImmutable(),
        );
        $encrypted = new EncryptedData(
            ciphertext: base64_encode('ciphertext'),
            iv: base64_encode('iv'),
            tag: base64_encode('tag'),
            algorithm: SymmetricAlgorithm::AES256GCM,
            metadata: ['keyId' => $keyId],
        );
        $expected = 'secret message';

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with($encrypted, $key)
            ->willReturn($expected);

        $result = $this->manager->decryptWithKey($encrypted, $keyId);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function decrypt_with_key_throws_on_key_id_mismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key ID mismatch');

        $encrypted = new EncryptedData(
            ciphertext: base64_encode('ciphertext'),
            iv: base64_encode('iv'),
            tag: base64_encode('tag'),
            algorithm: SymmetricAlgorithm::AES256GCM,
            metadata: ['keyId' => 'original-key'],
        );

        $this->manager->decryptWithKey($encrypted, 'different-key');
    }

    // =====================================================
    // SIGNING TESTS
    // =====================================================

    #[Test]
    public function sign_delegates_to_signer(): void
    {
        $data = 'message to sign';
        $privateKey = base64_encode(random_bytes(64));
        $algorithm = AsymmetricAlgorithm::ED25519;
        $signed = new SignedData(
            data: $data,
            signature: base64_encode('signature'),
            algorithm: $algorithm,
        );

        $this->signer
            ->expects($this->once())
            ->method('sign')
            ->with($data, $privateKey, $algorithm)
            ->willReturn($signed);

        $result = $this->manager->sign($data, $privateKey, $algorithm);

        $this->assertSame($signed, $result);
    }

    #[Test]
    public function verify_signature_delegates_to_signer(): void
    {
        $publicKey = base64_encode(random_bytes(32));
        $signed = new SignedData(
            data: 'message',
            signature: base64_encode('signature'),
            algorithm: AsymmetricAlgorithm::ED25519,
        );

        $this->signer
            ->expects($this->once())
            ->method('verify')
            ->with($signed, $publicKey)
            ->willReturn(true);

        $result = $this->manager->verifySignature($signed, $publicKey);

        $this->assertTrue($result);
    }

    #[Test]
    public function hmac_delegates_to_signer(): void
    {
        $data = 'message';
        $secret = 'secret-key';
        $expected = hash_hmac('sha256', $data, $secret);

        $this->signer
            ->expects($this->once())
            ->method('hmac')
            ->with($data, $secret)
            ->willReturn($expected);

        $result = $this->manager->hmac($data, $secret);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function verify_hmac_delegates_to_signer(): void
    {
        $data = 'message';
        $signature = 'hmac-signature';
        $secret = 'secret-key';

        $this->signer
            ->expects($this->once())
            ->method('verifyHmac')
            ->with($data, $signature, $secret)
            ->willReturn(true);

        $result = $this->manager->verifyHmac($data, $signature, $secret);

        $this->assertTrue($result);
    }

    // =====================================================
    // KEY MANAGEMENT TESTS
    // =====================================================

    #[Test]
    public function generate_encryption_key_creates_and_stores_key(): void
    {
        $keyId = 'new-key-001';
        $algorithm = SymmetricAlgorithm::AES256GCM;
        $expirationDays = 90;
        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: $algorithm,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable("+{$expirationDays} days"),
        );

        $this->keyGenerator
            ->expects($this->once())
            ->method('generateSymmetricKey')
            ->with($algorithm, $expirationDays)
            ->willReturn($key);

        $this->keyStorage
            ->expects($this->once())
            ->method('store')
            ->with($keyId, $key);

        $result = $this->manager->generateEncryptionKey($keyId, $algorithm, $expirationDays);

        $this->assertSame($key, $result);
    }

    #[Test]
    public function generate_key_pair_delegates_to_key_generator(): void
    {
        $algorithm = AsymmetricAlgorithm::ED25519;
        $keyPair = new KeyPair(
            publicKey: base64_encode(random_bytes(32)),
            privateKey: base64_encode(random_bytes(64)),
            algorithm: $algorithm,
        );

        $this->keyGenerator
            ->expects($this->once())
            ->method('generateKeyPair')
            ->with($algorithm)
            ->willReturn($keyPair);

        $result = $this->manager->generateKeyPair($algorithm);

        $this->assertSame($keyPair, $result);
    }

    #[Test]
    public function rotate_key_delegates_to_key_storage(): void
    {
        $keyId = 'rotating-key';
        $newKey = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new \DateTimeImmutable(),
        );

        $this->keyStorage
            ->expects($this->once())
            ->method('rotate')
            ->with($keyId)
            ->willReturn($newKey);

        $result = $this->manager->rotateKey($keyId);

        $this->assertSame($newKey, $result);
    }

    #[Test]
    public function get_key_delegates_to_key_storage(): void
    {
        $keyId = 'existing-key';
        $key = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new \DateTimeImmutable(),
        );

        $this->keyStorage
            ->expects($this->once())
            ->method('retrieve')
            ->with($keyId)
            ->willReturn($key);

        $result = $this->manager->getKey($keyId);

        $this->assertSame($key, $result);
    }

    #[Test]
    public function find_expiring_keys_delegates_to_key_storage(): void
    {
        $days = 7;
        $expected = ['key-1', 'key-2', 'key-3'];

        $this->keyStorage
            ->expects($this->once())
            ->method('findExpiringKeys')
            ->with($days)
            ->willReturn($expected);

        $result = $this->manager->findExpiringKeys($days);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function random_bytes_delegates_to_key_generator(): void
    {
        $length = 32;
        $expected = random_bytes($length);

        $this->keyGenerator
            ->expects($this->once())
            ->method('generateRandomBytes')
            ->with($length)
            ->willReturn($expected);

        $result = $this->manager->randomBytes($length);

        $this->assertSame($expected, $result);
    }

    // =====================================================
    // ANONYMIZATION TESTS
    // =====================================================

    #[Test]
    public function anonymize_delegates_to_anonymizer(): void
    {
        $data = 'sensitive data';
        $method = AnonymizationMethod::SALTED_HASH;
        $expected = new AnonymizedData(
            anonymizedValue: hash('sha256', $data . 'salt'),
            method: $method,
            anonymizedAt: new DateTimeImmutable(),
            salt: base64_encode('salt'),
        );

        $this->anonymizer
            ->expects($this->once())
            ->method('anonymize')
            ->with($data, $method, [])
            ->willReturn($expected);

        $result = $this->manager->anonymize($data, $method);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function pseudonymize_delegates_to_anonymizer(): void
    {
        $data = 'sensitive data';
        $keyId = 'pseudo-key-001';
        $expected = new PseudonymizedData(
            pseudonym: base64_encode('encrypted-pseudonym'),
            keyId: $keyId,
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->anonymizer
            ->expects($this->once())
            ->method('pseudonymize')
            ->with($data, $keyId)
            ->willReturn($expected);

        $result = $this->manager->pseudonymize($data, $keyId);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function de_pseudonymize_delegates_to_anonymizer(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: base64_encode('encrypted-pseudonym'),
            keyId: 'pseudo-key-001',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );
        $expected = 'original data';

        $this->anonymizer
            ->expects($this->once())
            ->method('dePseudonymize')
            ->with($pseudonymized)
            ->willReturn($expected);

        $result = $this->manager->dePseudonymize($pseudonymized);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function generate_pseudonym_delegates_to_anonymizer(): void
    {
        $data = 'user123';
        $context = 'customer_id';
        $keyId = 'context-key';
        $expected = 'consistent-pseudonym-hash';

        $this->anonymizer
            ->expects($this->once())
            ->method('generatePseudonym')
            ->with($data, $context, $keyId)
            ->willReturn($expected);

        $result = $this->manager->generatePseudonym($data, $context, $keyId);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function verify_anonymized_delegates_to_anonymizer(): void
    {
        $data = 'original';
        $anonymized = new AnonymizedData(
            anonymizedValue: hash('sha256', $data),
            method: AnonymizationMethod::SALTED_HASH,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->anonymizer
            ->expects($this->once())
            ->method('verifyAnonymized')
            ->with($data, $anonymized, [])
            ->willReturn(true);

        $result = $this->manager->verifyAnonymized($data, $anonymized);

        $this->assertTrue($result);
    }

    // =====================================================
    // DATA MASKING TESTS
    // =====================================================

    #[Test]
    public function mask_delegates_to_data_masker(): void
    {
        $data = 'test@example.com';
        $pattern = MaskingPattern::EMAIL;
        $expected = 'te**@example.com';

        $this->dataMasker
            ->expects($this->once())
            ->method('mask')
            ->with($data, $pattern)
            ->willReturn($expected);

        $result = $this->manager->mask($data, $pattern);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_with_pattern_delegates_to_data_masker(): void
    {
        $data = 'AB123CD';
        $pattern = '##***##';  // Custom pattern: show first 2, mask 3, show last 2
        $expected = 'AB***CD';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskWithPattern')
            ->with($data, $pattern, '*')
            ->willReturn($expected);

        $result = $this->manager->maskWithPattern($data, $pattern);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_email_delegates_to_data_masker(): void
    {
        $email = 'user@example.com';
        $expected = 'us**@example.com';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskEmail')
            ->with($email)
            ->willReturn($expected);

        $result = $this->manager->maskEmail($email);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_phone_delegates_to_data_masker(): void
    {
        $phone = '+60123456789';
        $expected = '+60****6789';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskPhone')
            ->with($phone)
            ->willReturn($expected);

        $result = $this->manager->maskPhone($phone);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_credit_card_delegates_to_data_masker(): void
    {
        $card = '4111111111111111';
        $expected = '411111******1111';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskCreditCard')
            ->with($card)
            ->willReturn($expected);

        $result = $this->manager->maskCreditCard($card);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_national_id_delegates_to_data_masker(): void
    {
        $id = '901231-14-5555';
        $expected = '******-**-5555';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskNationalId')
            ->with($id, 'MY')
            ->willReturn($expected);

        $result = $this->manager->maskNationalId($id, 'MY');

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_iban_delegates_to_data_masker(): void
    {
        $iban = 'DE89370400440532013000';
        $expected = 'DE89**************3000';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskIban')
            ->with($iban)
            ->willReturn($expected);

        $result = $this->manager->maskIban($iban);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_name_delegates_to_data_masker(): void
    {
        $name = 'John Doe';
        $expected = 'J*** D**';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskName')
            ->with($name)
            ->willReturn($expected);

        $result = $this->manager->maskName($name);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_address_delegates_to_data_masker(): void
    {
        $address = '123 Main Street, Anytown';
        $expected = '*** **** ******, *******';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskAddress')
            ->with($address)
            ->willReturn($expected);

        $result = $this->manager->maskAddress($address);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function mask_date_of_birth_delegates_to_data_masker(): void
    {
        $dob = '1990-12-31';
        $expected = '1990-**-**';

        $this->dataMasker
            ->expects($this->once())
            ->method('maskDateOfBirth')
            ->with($dob)
            ->willReturn($expected);

        $result = $this->manager->maskDateOfBirth($dob);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function redact_delegates_to_data_masker(): void
    {
        $data = 'sensitive information';
        $expected = '[REDACTED]';

        $this->dataMasker
            ->expects($this->once())
            ->method('redact')
            ->with($data, '[REDACTED]')
            ->willReturn($expected);

        $result = $this->manager->redact($data);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function is_already_masked_delegates_to_data_masker(): void
    {
        $data = 'se****ive';

        $this->dataMasker
            ->expects($this->once())
            ->method('isAlreadyMasked')
            ->with($data, '*')
            ->willReturn(true);

        $result = $this->manager->isAlreadyMasked($data);

        $this->assertTrue($result);
    }

    // =====================================================
    // CONSTRUCTOR TESTS
    // =====================================================

    #[Test]
    public function constructor_creates_default_anonymizer_and_masker(): void
    {
        // Create manager without explicit anonymizer/masker
        $manager = new CryptoManager(
            hasher: $this->hasher,
            encryptor: $this->encryptor,
            signer: $this->signer,
            keyGenerator: $this->keyGenerator,
            keyStorage: $this->keyStorage,
        );

        // Verify manager was created successfully and can be used
        // (using hash as a simple operation that doesn't depend on optional deps)
        $hashResult = new HashResult(hash: 'abc', algorithm: HashAlgorithm::SHA256);
        $this->hasher->method('hash')->willReturn($hashResult);
        
        $result = $manager->hash('test');
        $this->assertInstanceOf(HashResult::class, $result);
    }
}
