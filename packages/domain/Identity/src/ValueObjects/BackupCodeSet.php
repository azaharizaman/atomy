<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use InvalidArgumentException;

/**
 * Represents a collection of backup codes for MFA recovery.
 *
 * Backup code sets contain multiple one-time use codes. The set tracks
 * how many codes remain unused and can determine when regeneration should
 * be triggered (typically when ≤2 codes remain).
 *
 * @immutable
 */
final readonly class BackupCodeSet
{
    /**
     * @var BackupCode[] The backup codes in this set
     */
    public array $codes;

    /**
     * Create a new backup code set.
     *
     * @param BackupCode[] $codes The backup codes (typically 8-10)
     * @throws InvalidArgumentException If set is invalid
     */
    public function __construct(array $codes)
    {
        $this->validateCodes($codes);
        $this->codes = $codes;
    }

    /**
     * Validate the backup codes.
     *
     * @param BackupCode[] $codes
     * @throws InvalidArgumentException If codes are invalid
     */
    private function validateCodes(array $codes): void
    {
        if (count($codes) === 0) {
            throw new InvalidArgumentException('Backup code set cannot be empty');
        }

        foreach ($codes as $code) {
            if (!$code instanceof BackupCode) {
                throw new InvalidArgumentException('All items in set must be BackupCode instances');
            }
        }

        // Check for duplicate codes
        $uniqueCodes = array_unique(array_map(fn(BackupCode $c) => $c->code, $codes));
        if (count($uniqueCodes) !== count($codes)) {
            throw new InvalidArgumentException('Backup code set contains duplicate codes');
        }
    }

    /**
     * Get the number of codes in this set.
     */
    public function count(): int
    {
        return count($this->codes);
    }

    /**
     * Get the number of remaining (unconsumed) codes.
     */
    public function getRemainingCount(): int
    {
        return count(array_filter($this->codes, fn(BackupCode $code) => !$code->isConsumed()));
    }

    /**
     * Get the number of consumed codes.
     */
    public function getConsumedCount(): int
    {
        return count(array_filter($this->codes, fn(BackupCode $code) => $code->isConsumed()));
    }

    /**
     * Check if regeneration should be triggered.
     *
     * Regeneration is recommended when ≤2 codes remain to ensure
     * users don't run out of recovery options.
     */
    public function shouldTriggerRegeneration(int $threshold = 2): bool
    {
        return $this->getRemainingCount() <= $threshold;
    }

    /**
     * Check if all codes have been consumed.
     */
    public function isFullyConsumed(): bool
    {
        return $this->getRemainingCount() === 0;
    }

    /**
     * Check if no codes have been consumed.
     */
    public function isUnused(): bool
    {
        return $this->getConsumedCount() === 0;
    }

    /**
     * Find a code by its plaintext value.
     *
     * @return BackupCode|null The code if found, null otherwise
     */
    public function findCode(string $plaintext): ?BackupCode
    {
        foreach ($this->codes as $code) {
            if ($code->code === $plaintext) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Get all remaining (unconsumed) codes.
     *
     * @return BackupCode[]
     */
    public function getRemainingCodes(): array
    {
        return array_values(array_filter($this->codes, fn(BackupCode $code) => !$code->isConsumed()));
    }

    /**
     * Get all consumed codes.
     *
     * @return BackupCode[]
     */
    public function getConsumedCodes(): array
    {
        return array_values(array_filter($this->codes, fn(BackupCode $code) => $code->isConsumed()));
    }

    /**
     * Convert to array representation (for storage).
     *
     * @return array{
     *     total: int,
     *     remaining: int,
     *     consumed: int,
     *     codes: array<int, array{code: string, hash: string|null, consumed_at: string|null}>
     * }
     */
    public function toArray(): array
    {
        return [
            'total' => $this->count(),
            'remaining' => $this->getRemainingCount(),
            'consumed' => $this->getConsumedCount(),
            'codes' => array_map(fn(BackupCode $code) => $code->toArray(), $this->codes),
        ];
    }
}
