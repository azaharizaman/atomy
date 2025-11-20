<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\ValueObjects\HashResult;

/**
 * Native Hasher
 *
 * Implementation of HasherInterface using PHP's native hash functions
 * and Sodium extension for BLAKE2b.
 */
final readonly class NativeHasher implements HasherInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $data, HashAlgorithm $algorithm = HashAlgorithm::SHA256): HashResult
    {
        if ($algorithm === HashAlgorithm::BLAKE2B) {
            // Use Sodium for BLAKE2b
            $hashBinary = sodium_crypto_generichash($data);
            $hash = bin2hex($hashBinary);
        } else {
            // Use native hash() for SHA-2 family
            $hash = hash($algorithm->getNativeAlgorithm(), $data);
        }
        
        return new HashResult(
            hash: $hash,
            algorithm: $algorithm,
            salt: null,
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function verify(string $data, HashResult $expectedHash): bool
    {
        $actualHash = $this->hash($data, $expectedHash->algorithm);
        
        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedHash->hash, $actualHash->hash);
    }
}
