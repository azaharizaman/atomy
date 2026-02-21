<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Invalid Depreciation Method Exception
 *
 * Thrown when an unsupported or invalid depreciation method is requested,
 * or when the current tier does not support the requested method.
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class InvalidDepreciationMethodException extends DepreciationException
{
    protected const ERROR_CODE = 'INVALID_DEPRECIATION_METHOD';

    public function __construct(
        public readonly string $method,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        $fullMessage = sprintf(
            'Invalid depreciation method "%s": %s',
            $method,
            $message ?: 'Method not supported or not available in current tier'
        );
        
        parent::__construct($fullMessage, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public static function notSupported(string $method): self
    {
        return new self($method, 'Method is not supported by this implementation');
    }

    public static function tierNotAvailable(string $method, int $requiredTier, int $currentTier): self
    {
        return new self(
            $method,
            sprintf('Method requires tier %d but current tier is %d', $requiredTier, $currentTier)
        );
    }

    public static function notApplicableToAsset(string $method, string $assetId): self
    {
        return new self(
            $method,
            sprintf('Method not applicable to asset "%s"', $assetId)
        );
    }

    public static function missingConfiguration(string $method): self
    {
        return new self($method, 'Method is not configured or registered');
    }
}
