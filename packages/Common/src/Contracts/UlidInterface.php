<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * ULID Generator Interface for standardized identifier generation.
 *
 * Provides a contract for generating ULIDs (Universally Unique Lexicographically
 * Sortable Identifiers) across all Nexus packages. ULIDs are the standard primary
 * key format for all Nexus entities.
 *
 * **ULID Properties:**
 * - 128-bit unique identifiers (same size as UUID)
 * - Lexicographically sortable (timestamp-based prefix enables time ordering)
 * - Case-insensitive, URL-safe Base32 encoded
 * - 26 characters long (compact representation)
 * - Monotonically increasing within same millisecond
 *
 * **When to Use:**
 * - Primary keys for all Nexus entities
 * - External reference IDs (invoice numbers not applicable)
 * - Correlation IDs for distributed tracing
 * - Any unique identifier needing time-based sorting
 *
 * **When NOT to Use:**
 * - Human-readable sequential numbers → Use `Nexus\Sequencing`
 * - Temporary identifiers → Use random strings
 * - When sorting by ID must NOT reveal creation order
 *
 * **Expected Behavior:**
 * - `generate()` returns uppercase 26-character ULID string
 * - `isValid()` performs format validation (not existence check)
 * - `getTimestamp()` extracts embedded creation timestamp
 * - Thread-safe generation (no duplicates under concurrent load)
 *
 * @example Example implementation using Symfony Uid:
 * ```php
 * final readonly class UlidGenerator implements UlidInterface
 * {
 *     public function generate(): string
 *     {
 *         return (new \Symfony\Component\Uid\Ulid())->toBase32();
 *     }
 *
 *     public function isValid(string $ulid): bool
 *     {
 *         return \Symfony\Component\Uid\Ulid::isValid($ulid);
 *     }
 *
 *     public function getTimestamp(string $ulid): \DateTimeImmutable
 *     {
 *         $ulidObj = \Symfony\Component\Uid\Ulid::fromString($ulid);
 *         return $ulidObj->getDateTime();
 *     }
 * }
 * ```
 *
 * @example Usage in entity creation:
 * ```php
 * final readonly class InvoiceManager
 * {
 *     public function __construct(
 *         private UlidInterface $ulid,
 *         private InvoicePersistInterface $repository
 *     ) {}
 *
 *     public function create(array $data): Invoice
 *     {
 *         $invoice = new Invoice(
 *             id: $this->ulid->generate(),
 *             // ... other properties
 *         );
 *
 *         return $this->repository->save($invoice);
 *     }
 * }
 * ```
 */
interface UlidInterface
{
    /**
     * Generate a new ULID as a string.
     * 
     * @return string A new ULID in canonical string format (26 characters, uppercase)
     */
    public function generate(): string;

    /**
     * Validate if a given string is a valid ULID format.
     * 
     * @param string $ulid The string to validate
     * @return bool True if valid ULID format, false otherwise
     */
    public function isValid(string $ulid): bool;

    /**
     * Parse a ULID string and extract its timestamp.
     * 
     * @param string $ulid The ULID to parse
     * @return \DateTimeImmutable The timestamp when the ULID was generated
     * @throws \InvalidArgumentException If the ULID is invalid
     */
    public function getTimestamp(string $ulid): \DateTimeImmutable;
}
