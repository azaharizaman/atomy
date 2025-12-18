<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\HumanResourceOperations\Contracts\SecureIdGeneratorInterface;

/**
 * Secure ID generator implementation using Nexus\Crypto.
 *
 * Provides cryptographically secure ID generation for:
 * - Employee IDs
 * - Payslip IDs
 * - Leave request IDs
 * - Workflow IDs
 *
 * This implementation leverages Nexus\Crypto for post-quantum
 * cryptography (PQC) readiness and algorithm agility.
 */
final readonly class SecureIdGenerator implements SecureIdGeneratorInterface
{
    public function __construct(
        private CryptoManagerInterface $crypto,
    ) {}

    /**
     * @inheritDoc
     */
    public function generateId(string $prefix = '', int $length = 16): string
    {
        $randomPart = $this->randomHex($length);

        if ($prefix === '') {
            return $randomPart;
        }

        return $prefix . $randomPart;
    }

    /**
     * @inheritDoc
     */
    public function generateUuid(): string
    {
        // Generate 16 random bytes for UUID v4
        $data = $this->crypto->randomBytes(16);

        // Set version to 4 (random)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @inheritDoc
     */
    public function randomBytes(int $length): string
    {
        return $this->crypto->randomBytes($length);
    }

    /**
     * Generate hex-encoded random bytes.
     *
     * Note: Each byte produces 2 hex characters, so the output
     * string length is 2 * $length. For example:
     * - randomHex(4) returns 8 hex characters
     * - randomHex(8) returns 16 hex characters
     * - randomHex(16) returns 32 hex characters
     *
     * @inheritDoc
     */
    public function randomHex(int $length): string
    {
        return bin2hex($this->crypto->randomBytes($length));
    }
}
