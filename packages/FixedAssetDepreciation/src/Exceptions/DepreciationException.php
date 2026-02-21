<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Base Exception for Depreciation Package
 *
 * All depreciation-related exceptions extend from this class.
 * Provides consistent error handling and context propagation.
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class DepreciationException extends \RuntimeException
{
    protected const ERROR_CODE = 'DEPRECIATION_ERROR';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return static::ERROR_CODE;
    }

    public static function withContext(
        string $message,
        array $context = [],
        ?\Throwable $previous = null
    ): self {
        $contextString = !empty($context)
            ? ' Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES)
            : '';
        
        return new self($message . $contextString, 0, $previous);
    }
}
