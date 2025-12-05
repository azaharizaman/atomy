<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be serialized and deserialized.
 *
 * **When to Use:**
 * Implement this interface when value objects need to be:
 * - Stored in databases (as JSON columns)
 * - Transmitted over APIs (JSON responses)
 * - Cached (Redis, Memcached)
 * - Logged or audited
 * - Passed through message queues
 *
 * **Expected Behavior:**
 * - toArray() returns a plain PHP array suitable for JSON encoding
 * - toString() returns a human-readable string representation
 * - fromArray() reconstructs the value object from array data
 * - Serialization MUST be lossless (fromArray(toArray()) === original)
 * - Arrays should contain only primitives (no objects, no closures)
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements SerializableVO
 * {
 *     public function toArray(): array
 *     {
 *         return [
 *             'amount' => $this->amountInMinorUnits,
 *             'currency' => $this->currency,
 *         ];
 *     }
 *
 *     public function toString(): string
 *     {
 *         return sprintf('%d %s', $this->amountInMinorUnits, $this->currency);
 *     }
 *
 *     public static function fromArray(array $data): static
 *     {
 *         return new self(
 *             amountInMinorUnits: $data['amount'],
 *             currency: $data['currency']
 *         );
 *     }
 * }
 * ```
 *
 * **Usage with JSON:**
 * ```php
 * // Serialize to JSON
 * $json = json_encode($money->toArray());
 *
 * // Deserialize from JSON
 * $data = json_decode($json, true);
 * $money = Money::fromArray($data);
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\TenantId
 */
interface SerializableVO
{
    /**
     * Convert to array representation for serialization.
     *
     * The returned array should contain only primitive types
     * (strings, integers, floats, booleans, arrays) suitable for
     * JSON encoding.
     *
     * @return array<string, mixed> Serializable array representation
     */
    public function toArray(): array;

    /**
     * Convert to human-readable string representation.
     *
     * Useful for logging, debugging, and display purposes.
     *
     * @return string String representation of the value object
     */
    public function toString(): string;

    /**
     * Create instance from array data.
     *
     * This is the inverse of toArray() and should reconstruct
     * an equivalent value object from serialized data.
     *
     * @param array<string, mixed> $data Serialized array data
     * @return static New instance of the value object
     * @throws \InvalidArgumentException If data is invalid or incomplete
     */
    public static function fromArray(array $data): static;
}
