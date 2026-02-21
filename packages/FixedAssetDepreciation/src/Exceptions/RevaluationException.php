<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Base exception for revaluation-related errors.
 *
 * This is the base exception for all revaluation operations including
 * revaluation calculation, reversal, and GL posting.
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class RevaluationException extends DepreciationException
{
    protected string $errorCode = 'REVALUATION_ERROR';

    /**
     * @param string $assetId The asset identifier
     * @param string $message The error message
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        public readonly string $assetId,
        string $message,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create for invalid revaluation.
     *
     * @param string $assetId Asset ID
     * @param string $reason Reason for failure
     * @return self
     */
    public static function invalid(
        string $assetId,
        string $reason = 'Invalid revaluation parameters'
    ): self {
        return new self($assetId, $reason);
    }

    /**
     * Create for revaluation calculation failure.
     *
     * @param string $assetId Asset ID
     * @param string $reason Calculation error
     * @return self
     */
    public static function calculationFailed(
        string $assetId,
        string $reason = 'Failed to calculate revaluation'
    ): self {
        return new self(
            $assetId,
            sprintf('Revaluation calculation failed: %s', $reason)
        );
    }

    /**
     * Create for reversal failure.
     *
     * @param string $assetId Asset ID
     * @param string $revaluationId Revaluation ID
     * @param string $reason Reason for failure
     * @return self
     */
    public static function reversalFailed(
        string $assetId,
        string $revaluationId,
        string $reason = 'Cannot reverse revaluation'
    ): self {
        return new self(
            $assetId,
            sprintf('Failed to reverse revaluation %s: %s', $revaluationId, $reason)
        );
    }

    /**
     * Create for posting failure.
     *
     * @param string $assetId Asset ID
     * @param string $reason Reason for failure
     * @return self
     */
    public static function postingFailed(
        string $assetId,
        string $reason = 'Failed to post revaluation to GL'
    ): self {
        return new self(
            $assetId,
            sprintf('Revaluation posting failed: %s', $reason)
        );
    }

    /**
     * Create for invalid parameters.
     *
     * @param string $assetId Asset ID
     * @param array $errors Validation errors
     * @return self
     */
    public static function invalidParameters(
        string $assetId,
        array $errors
    ): self {
        return new self(
            $assetId,
            sprintf('Invalid revaluation parameters: %s', implode(', ', $errors))
        );
    }

    /**
     * Create for tier not available.
     *
     * @param string $assetId Asset ID
     * @return self
     */
    public static function tierNotAvailable(string $assetId): self
    {
        return new self(
            $assetId,
            'Revaluation requires Tier 2 (Advanced) or higher'
        );
    }

    /**
     * Create for disposed asset.
     *
     * @param string $assetId Asset ID
     * @return self
     */
    public static function disposedAsset(string $assetId): self
    {
        return new self(
            $assetId,
            'Cannot revalue a disposed asset'
        );
    }

    /**
     * Create for invalid salvage value.
     *
     * @param string $assetId Asset ID
     * @param float $salvageValue The invalid salvage value
     * @param float $cost The asset cost
     * @return self
     */
    public static function invalidSalvageValue(string $assetId, float $salvageValue, float $cost): self
    {
        return new self(
            $assetId,
            sprintf('Salvage value %.2f cannot exceed asset cost %.2f', $salvageValue, $cost)
        );
    }

    /**
     * Get the asset ID.
     *
     * @return string
     */
    public function getAssetId(): string
    {
        return $this->assetId;
    }
}
