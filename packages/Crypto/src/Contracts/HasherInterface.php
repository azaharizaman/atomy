<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\ValueObjects\HashResult;

/**
 * Hasher Interface
 *
 * Provides cryptographic hashing for data integrity verification.
 * Implemented by the service layer (e.g., NativeHasher using hash() or Sodium).
 */
interface HasherInterface
{
    /**
     * Hash data using specified algorithm
     *
     * @param string $data Data to hash
     * @param HashAlgorithm $algorithm Hash algorithm to use (default: SHA256)
     * @return HashResult Hash result with algorithm metadata
     */
    public function hash(string $data, HashAlgorithm $algorithm = HashAlgorithm::SHA256): HashResult;
    
    /**
     * Verify that data matches expected hash
     *
     * Uses constant-time comparison to prevent timing attacks.
     *
     * @param string $data Original data
     * @param HashResult $expectedHash Expected hash result
     * @return bool True if hash matches
     */
    public function verify(string $data, HashResult $expectedHash): bool;
}
