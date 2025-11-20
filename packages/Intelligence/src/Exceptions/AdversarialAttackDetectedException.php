<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Exceptions;

/**
 * Exception thrown when adversarial input is detected
 */
class AdversarialAttackDetectedException extends IntelligenceException
{
    /**
     * @param string $featuresHash Hash of suspicious features
     * @param array<string> $suspiciousFeatures List of suspicious feature names
     */
    public static function forDetection(string $featuresHash, array $suspiciousFeatures): self
    {
        return new self(
            "Adversarial attack detected. Features hash: {$featuresHash}. " .
            "Suspicious features: " . implode(', ', $suspiciousFeatures) . ". " .
            "This request has been blocked and logged for security review."
        );
    }
}
