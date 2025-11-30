<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Services;

use Nexus\AuditLogger\Contracts\AuditConfigInterface;

/**
 * Service for masking sensitive data in audit logs
 * Satisfies: FUN-AUD-0192
 *
 * @package Nexus\AuditLogger\Services
 */
class SensitiveDataMasker
{
    private const MASK = '***MASKED***';

    private AuditConfigInterface $config;
    private array $sensitivePatterns;

    public function __construct(AuditConfigInterface $config)
    {
        $this->config = $config;
        $this->sensitivePatterns = $config->getSensitiveFieldPatterns();
    }

    /**
     * Mask sensitive data in properties array
     *
     * @param array $properties
     * @return array
     */
    public function maskSensitiveData(array $properties): array
    {
        return $this->maskArray($properties);
    }

    /**
     * Recursively mask sensitive data in arrays
     *
     * @param array $data
     * @return array
     */
    private function maskArray(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $masked[$key] = self::MASK;
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskArray($value);
            } elseif (is_string($value) && $this->containsSensitiveData($value)) {
                $masked[$key] = self::MASK;
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Check if a key name indicates sensitive data
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach ($this->sensitivePatterns as $pattern) {
            // If pattern starts with '/', treat as regex
            if (str_starts_with($pattern, '/')) {
                if (preg_match($pattern, $lowerKey)) {
                    return true;
                }
            } else {
                // Otherwise, simple string match
                if (str_contains($lowerKey, strtolower($pattern))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if string value contains sensitive data patterns
     *
     * @param string $value
     * @return bool
     */
    private function containsSensitiveData(string $value): bool
    {
        // Check for common sensitive data patterns
        $patterns = [
            '/\b\d{16}\b/',  // Credit card numbers
            '/\b\d{3}-\d{2}-\d{4}\b/',  // SSN format
            '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i',  // Bearer tokens
            '/api[_-]?key[:\s=]+[A-Za-z0-9]+/i',  // API keys
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
