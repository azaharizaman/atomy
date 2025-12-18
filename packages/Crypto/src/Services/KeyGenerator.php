<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use DateTimeImmutable;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\FeatureNotImplementedException;
use Nexus\Crypto\Exceptions\UnsupportedAlgorithmException;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\KeyPair;

/**
 * Key Generator
 *
 * Implementation of KeyGeneratorInterface using Sodium and OpenSSL
 * for cryptographically secure key generation.
 */
final readonly class KeyGenerator implements KeyGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateSymmetricKey(
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?int $expirationDays = null
    ): EncryptionKey {
        $keyLength = $algorithm->getKeyLength();
        $keyBinary = random_bytes($keyLength);
        
        $createdAt = new DateTimeImmutable();
        $expiresAt = $expirationDays !== null
            ? $createdAt->modify("+{$expirationDays} days")
            : null;
        
        return new EncryptionKey(
            key: base64_encode($keyBinary),
            algorithm: $algorithm,
            createdAt: $createdAt,
            expiresAt: $expiresAt,
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateKeyPair(
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): KeyPair {
        // Check if algorithm is implemented
        if (!$algorithm->isImplemented()) {
            throw FeatureNotImplementedException::pqcAlgorithm($algorithm->value);
        }
        
        return match ($algorithm) {
            AsymmetricAlgorithm::ED25519 => $this->generateEd25519KeyPair(),
            AsymmetricAlgorithm::RSA2048 => $this->generateRsaKeyPair(2048),
            AsymmetricAlgorithm::RSA4096 => $this->generateRsaKeyPair(4096),
            AsymmetricAlgorithm::ECDSAP256 => throw UnsupportedAlgorithmException::asymmetric($algorithm),
            AsymmetricAlgorithm::HMACSHA256 => throw UnsupportedAlgorithmException::asymmetric($algorithm),
            default => throw UnsupportedAlgorithmException::asymmetric($algorithm),
        };
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateRandomBytes(int $length): string
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException("Length must be positive");
        }
        
        // Prevent memory exhaustion attacks (max 1MB)
        if ($length > 1048576) {
            throw new \InvalidArgumentException("Length must not exceed 1MB (1048576 bytes)");
        }
        
        return random_bytes($length);
    }
    
    /**
     * Generate Ed25519 key pair using Sodium
     */
    private function generateEd25519KeyPair(): KeyPair
    {
        $keyPairBinary = sodium_crypto_sign_keypair();
        
        $publicKeyBinary = sodium_crypto_sign_publickey($keyPairBinary);
        $privateKeyBinary = sodium_crypto_sign_secretkey($keyPairBinary);
        
        // Zero out key pair from memory
        sodium_memzero($keyPairBinary);
        
        return new KeyPair(
            publicKey: base64_encode($publicKeyBinary),
            privateKey: base64_encode($privateKeyBinary),
            algorithm: AsymmetricAlgorithm::ED25519,
        );
    }
    
    /**
     * Generate RSA key pair using OpenSSL
     */
    private function generateRsaKeyPair(int $keySize): KeyPair
    {
        $config = [
            'private_key_bits' => $keySize,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $resource = openssl_pkey_new($config);
        
        if ($resource === false) {
            throw new \RuntimeException("Failed to generate RSA key pair");
        }
        
        // Export private key
        openssl_pkey_export($resource, $privateKeyPem);
        
        // Export public key
        $details = openssl_pkey_get_details($resource);
        $publicKeyPem = $details['key'];
        
        $algorithm = $keySize === 2048
            ? AsymmetricAlgorithm::RSA2048
            : AsymmetricAlgorithm::RSA4096;
        
        return new KeyPair(
            publicKey: base64_encode($publicKeyPem),
            privateKey: base64_encode($privateKeyPem),
            algorithm: $algorithm,
        );
    }
}
