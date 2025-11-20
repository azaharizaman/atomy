<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Feature Not Implemented Exception
 *
 * Thrown when attempting to use a feature that is planned but not yet implemented.
 * Used for Phase 2/3 post-quantum cryptography features.
 */
class FeatureNotImplementedException extends CryptoException
{
    public static function pqcAlgorithm(string $algorithmName): self
    {
        return new self(
            "Post-quantum algorithm '{$algorithmName}' is not yet implemented. " .
            "This is a Phase 2 feature planned for Q3 2026. " .
            "Please use classical algorithms or wait for PQC library maturity."
        );
    }
    
    public static function hybridMode(): self
    {
        return new self(
            "Hybrid cryptography mode (dual classical + PQC) is not yet implemented. " .
            "This is a Phase 2 feature planned for Q3 2026."
        );
    }
}
