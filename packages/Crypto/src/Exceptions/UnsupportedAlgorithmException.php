<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;

/**
 * Unsupported Algorithm Exception
 *
 * Thrown when attempting to use an algorithm that is not supported
 * by the current implementation.
 */
class UnsupportedAlgorithmException extends CryptoException
{
    public static function hash(HashAlgorithm $algorithm): self
    {
        return new self("Hash algorithm '{$algorithm->value}' is not supported by this implementation");
    }
    
    public static function symmetric(SymmetricAlgorithm $algorithm): self
    {
        return new self("Symmetric algorithm '{$algorithm->value}' is not supported by this implementation");
    }
    
    public static function asymmetric(AsymmetricAlgorithm $algorithm): self
    {
        return new self("Asymmetric algorithm '{$algorithm->value}' is not supported by this implementation");
    }
}
