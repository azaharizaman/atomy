<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\StreamNameGeneratorInterface;
use Nexus\EventStream\Exceptions\InvalidStreamNameException;

/**
 * Default Stream Name Generator
 *
 * Generates canonical stream identifiers with validation.
 * Format: {context}-{aggregate-type}-{aggregate-id} (lowercase)
 *
 * Validation Rules:
 * - Maximum length: 255 characters
 * - Allowed characters: [a-z0-9\-_] (alphanumeric, hyphen, underscore)
 * - All components must be non-empty
 * - Automatic lowercase conversion for consistency
 *
 * Examples:
 * - finance-account-01jcqr8xyz1234567890abcdef
 * - inventory-stock-02jd5t9abc9876543210fedcba
 * - sales-order-03je6u0def5432109876543210
 *
 * Requirements satisfied:
 * - FUN-EVS-7234: Stream naming validation with 255-char limit
 * - SEC-EVS-7511: Stream name input validation
 * - ARC-EVS-7015: Mandatory naming convention enforcement
 *
 * @package Nexus\EventStream\Services
 */
final readonly class DefaultStreamNameGenerator implements StreamNameGeneratorInterface
{
    private const MAX_LENGTH = 255;
    private const VALID_PATTERN = '/^[a-z0-9\-_]+$/';

    public function generate(string $context, string $aggregateType, string $aggregateId): string
    {
        // Validate components are not empty
        $this->validateNotEmpty($context, 'context');
        $this->validateNotEmpty($aggregateType, 'aggregateType');
        $this->validateNotEmpty($aggregateId, 'aggregateId');

        // Generate stream name in canonical format (lowercase)
        $streamName = strtolower(sprintf('%s-%s-%s', $context, $aggregateType, $aggregateId));

        // Validate length
        $this->validateLength($streamName);

        // Validate characters
        $this->validateCharacters($streamName);

        return $streamName;
    }

    /**
     * Validate component is not empty or whitespace
     *
     * @param string $value Component value
     * @param string $componentName Component name for error message
     * @return void
     * @throws InvalidStreamNameException
     */
    private function validateNotEmpty(string $value, string $componentName): void
    {
        if (trim($value) === '') {
            throw InvalidStreamNameException::emptyComponent($componentName);
        }
    }

    /**
     * Validate stream name length
     *
     * @param string $streamName Generated stream name
     * @return void
     * @throws InvalidStreamNameException
     */
    private function validateLength(string $streamName): void
    {
        $length = strlen($streamName);

        if ($length > self::MAX_LENGTH) {
            throw InvalidStreamNameException::tooLong($streamName, $length, self::MAX_LENGTH);
        }
    }

    /**
     * Validate stream name contains only allowed characters
     *
     * @param string $streamName Generated stream name
     * @return void
     * @throws InvalidStreamNameException
     */
    private function validateCharacters(string $streamName): void
    {
        if (!preg_match(self::VALID_PATTERN, $streamName)) {
            throw InvalidStreamNameException::invalidCharacters($streamName);
        }
    }
}
