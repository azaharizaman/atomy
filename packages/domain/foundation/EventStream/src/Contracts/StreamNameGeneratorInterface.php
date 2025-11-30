<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use Nexus\EventStream\Exceptions\InvalidStreamNameException;

/**
 * Stream Name Generator Interface
 *
 * Mandatory contract for generating canonical, validated stream identifiers.
 * Enforces consistent naming across the event store to enable:
 * - Database partitioning by stream prefix
 * - Efficient querying and filtering
 * - Clear debugging and operational visibility
 *
 * Canonical Format: {context}-{aggregate-type}-{aggregate-id}
 * Example: finance-account-01JCQR8XYZABC1234567890
 *
 * Validation Rules:
 * - Maximum length: 255 characters (database compatibility)
 * - Allowed characters: alphanumeric, hyphens, underscores [a-z0-9\-_]
 * - Case: lowercase recommended for consistency
 *
 * Requirements satisfied:
 * - FUN-EVS-7234: Stream naming validation with 255-char limit
 * - SEC-EVS-7511: Stream name input validation
 * - ARC-EVS-7015: Mandatory naming convention
 * - PER-EVS-7313: Partition key implications
 *
 * @package Nexus\EventStream\Contracts
 */
interface StreamNameGeneratorInterface
{
    /**
     * Generate a validated stream identifier.
     *
     * @param string $context Domain context (e.g., 'finance', 'inventory')
     * @param string $aggregateType Aggregate type (e.g., 'account', 'stock')
     * @param string $aggregateId Unique aggregate identifier (ULID/UUID)
     * @return string Validated stream name in canonical format
     * @throws InvalidStreamNameException If validation fails (length/characters)
     */
    public function generate(string $context, string $aggregateType, string $aggregateId): string;
}
