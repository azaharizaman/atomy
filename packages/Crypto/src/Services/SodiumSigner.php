<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Exceptions\FeatureNotImplementedException;
use Nexus\Crypto\Exceptions\SignatureException;
use Nexus\Crypto\Exceptions\UnsupportedAlgorithmException;
use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Sodium Signer
 *
 * Implementation of AsymmetricSignerInterface using Sodium for Ed25519
 * and native PHP functions for HMAC signing.
 */
final readonly class SodiumSigner implements AsymmetricSignerInterface
{
    /**
     * {@inheritdoc}
     */
    public function sign(
        string $data,
        string $privateKey,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): SignedData {
        // Check if algorithm is implemented
        if (!$algorithm->isImplemented()) {
            throw FeatureNotImplementedException::pqcAlgorithm($algorithm->value);
        }
        
        try {
            $signature = match ($algorithm) {
                AsymmetricAlgorithm::HMACSHA256 => $this->signHmac($data, $privateKey),
                AsymmetricAlgorithm::ED25519 => $this->signEd25519($data, $privateKey),
                AsymmetricAlgorithm::RSA2048,
                AsymmetricAlgorithm::RSA4096,
                AsymmetricAlgorithm::ECDSAP256 => throw UnsupportedAlgorithmException::asymmetric($algorithm),
                default => throw UnsupportedAlgorithmException::asymmetric($algorithm),
            };
        } catch (UnsupportedAlgorithmException|FeatureNotImplementedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw SignatureException::generationFailed($e->getMessage());
        }
        
        return new SignedData(
            data: $data,
            signature: $signature,
            algorithm: $algorithm,
            publicKey: null, // Will be extracted in real implementation
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function verify(SignedData $signed, string $publicKey): bool
    {
        // Check if algorithm is implemented
        if (!$signed->algorithm->isImplemented()) {
            throw FeatureNotImplementedException::pqcAlgorithm($signed->algorithm->value);
        }
        
        try {
            return match ($signed->algorithm) {
                AsymmetricAlgorithm::HMACSHA256 => $this->verifyHmac($signed->data, $signed->signature, $publicKey),
                AsymmetricAlgorithm::ED25519 => $this->verifyEd25519($signed->data, $signed->signature, $publicKey),
                AsymmetricAlgorithm::RSA2048,
                AsymmetricAlgorithm::RSA4096,
                AsymmetricAlgorithm::ECDSAP256 => throw UnsupportedAlgorithmException::asymmetric($signed->algorithm),
                default => throw UnsupportedAlgorithmException::asymmetric($signed->algorithm),
            };
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function hmac(
        string $data,
        string $secret,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::HMACSHA256
    ): string {
        if ($algorithm !== AsymmetricAlgorithm::HMACSHA256) {
            throw UnsupportedAlgorithmException::asymmetric($algorithm);
        }
        
        return hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * {@inheritdoc}
     */
    public function verifyHmac(
        string $data,
        string $signature,
        string $secret,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::HMACSHA256
    ): bool {
        $expected = $this->hmac($data, $secret, $algorithm);
        
        // Constant-time comparison
        return hash_equals($expected, $signature);
    }
    
    /**
     * Sign with HMAC-SHA256
     */
    private function signHmac(string $data, string $secret): string
    {
        return hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * Sign with Ed25519 using Sodium
     */
    private function signEd25519(string $data, string $privateKey): string
    {
        // Decode base64 private key
        $privateKeyBinary = base64_decode($privateKey, true);
        
        if ($privateKeyBinary === false || strlen($privateKeyBinary) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw SignatureException::generationFailed("Invalid Ed25519 private key format");
        }
        
        $signatureBinary = sodium_crypto_sign_detached($data, $privateKeyBinary);
        
        return base64_encode($signatureBinary);
    }
    
    /**
     * Verify Ed25519 signature using Sodium
     */
    private function verifyEd25519(string $data, string $signature, string $publicKey): bool
    {
        // Decode base64
        $signatureBinary = base64_decode($signature, true);
        $publicKeyBinary = base64_decode($publicKey, true);
        
        if ($signatureBinary === false || $publicKeyBinary === false) {
            return false;
        }
        
        if (strlen($publicKeyBinary) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }
        
        return sodium_crypto_sign_verify_detached($signatureBinary, $data, $publicKeyBinary);
    }
}
