<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Asymmetric Signer Interface
 *
 * Provides digital signatures and HMAC signing for data authenticity and integrity.
 * Implemented by the service layer (e.g., SodiumSigner, OpenSSLSigner).
 */
interface AsymmetricSignerInterface
{
    /**
     * Sign data with private key or secret
     *
     * For HMAC algorithms, $privateKey is the shared secret.
     * For asymmetric algorithms (Ed25519, RSA, ECDSA), $privateKey is the private key.
     *
     * @param string $data Data to sign
     * @param string $privateKey Private key or HMAC secret (base64-encoded for asymmetric)
     * @param AsymmetricAlgorithm $algorithm Signing algorithm (default: Ed25519)
     * @return SignedData Signed data with signature and algorithm
     */
    public function sign(
        string $data,
        string $privateKey,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): SignedData;
    
    /**
     * Verify signature
     *
     * For HMAC algorithms, $publicKey is the shared secret.
     * For asymmetric algorithms, $publicKey is the public key.
     *
     * @param SignedData $signed Signed data to verify
     * @param string $publicKey Public key or HMAC secret (base64-encoded for asymmetric)
     * @return bool True if signature is valid
     */
    public function verify(SignedData $signed, string $publicKey): bool;
    
    /**
     * Generate HMAC signature (convenience method)
     *
     * @param string $data Data to sign
     * @param string $secret Shared secret
     * @param AsymmetricAlgorithm $algorithm HMAC algorithm (default: HMACSHA256)
     * @return string Hex-encoded signature
     */
    public function hmac(
        string $data,
        string $secret,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::HMACSHA256
    ): string;
    
    /**
     * Verify HMAC signature (convenience method)
     *
     * @param string $data Original data
     * @param string $signature Hex-encoded signature
     * @param string $secret Shared secret
     * @param AsymmetricAlgorithm $algorithm HMAC algorithm (default: HMACSHA256)
     * @return bool True if signature is valid
     */
    public function verifyHmac(
        string $data,
        string $signature,
        string $secret,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::HMACSHA256
    ): bool;
}
