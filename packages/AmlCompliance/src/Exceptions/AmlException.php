<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Exceptions;

/**
 * Base exception for AML Compliance domain
 * 
 * All AmlCompliance package exceptions extend this class to enable
 * consistent error handling and identification.
 */
class AmlException extends \Exception
{
    /**
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     * @param array<string, mixed> $context Additional error context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for configuration error
     */
    public static function configurationError(string $setting, string $reason): self
    {
        return new self(
            message: sprintf('AML configuration error for "%s": %s', $setting, $reason),
            code: 1001,
            context: ['setting' => $setting, 'reason' => $reason]
        );
    }

    /**
     * Create exception for invalid risk level
     */
    public static function invalidRiskLevel(int $score): self
    {
        return new self(
            message: sprintf('Invalid risk score: %d. Score must be between 0 and 100.', $score),
            code: 1002,
            context: ['score' => $score]
        );
    }

    /**
     * Create exception for jurisdiction not found
     */
    public static function jurisdictionNotFound(string $countryCode): self
    {
        return new self(
            message: sprintf('Jurisdiction not found: %s', $countryCode),
            code: 1003,
            context: ['country_code' => $countryCode]
        );
    }

    /**
     * Create exception for party not found
     */
    public static function partyNotFound(string $partyId): self
    {
        return new self(
            message: sprintf('Party not found: %s', $partyId),
            code: 1004,
            context: ['party_id' => $partyId]
        );
    }

    /**
     * Create exception for operation not allowed
     */
    public static function operationNotAllowed(string $operation, string $reason): self
    {
        return new self(
            message: sprintf('Operation "%s" not allowed: %s', $operation, $reason),
            code: 1005,
            context: ['operation' => $operation, 'reason' => $reason]
        );
    }

    /**
     * Create exception for external service error
     */
    public static function externalServiceError(string $service, string $reason): self
    {
        return new self(
            message: sprintf('External service error (%s): %s', $service, $reason),
            code: 1006,
            context: ['service' => $service, 'reason' => $reason]
        );
    }

    /**
     * Get structured error response
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error_type' => (new \ReflectionClass($this))->getShortName(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
