<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\MatchingResultInterface;
use Nexus\Payable\Contracts\LineMatchingResultInterface;
use Nexus\Payable\Enums\MatchingStatus;

/**
 * Matching result implementation.
 */
final readonly class MatchingResult implements MatchingResultInterface
{
    /**
     * @param array<LineMatchingResultInterface> $lineResults
     * @param array $variances
     */
    public function __construct(
        private MatchingStatus $status,
        private array $lineResults,
        private array $variances = []
    ) {}

    public function isMatched(): bool
    {
        return $this->status === MatchingStatus::MATCHED;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getVariances(): array
    {
        return $this->variances;
    }

    public function getLineResults(): array
    {
        return $this->lineResults;
    }

    public function getTotalQtyVariancePercent(): float
    {
        if (empty($this->lineResults)) {
            return 0.0;
        }

        $totalVariance = 0.0;
        foreach ($this->lineResults as $lineResult) {
            $totalVariance += abs($lineResult->getQtyVariancePercent());
        }

        return $totalVariance / count($this->lineResults);
    }

    public function getTotalPriceVariancePercent(): float
    {
        if (empty($this->lineResults)) {
            return 0.0;
        }

        $totalVariance = 0.0;
        foreach ($this->lineResults as $lineResult) {
            $totalVariance += abs($lineResult->getPriceVariancePercent());
        }

        return $totalVariance / count($this->lineResults);
    }

    public function isWithinTolerance(): bool
    {
        foreach ($this->lineResults as $lineResult) {
            if (!$lineResult->isQtyWithinTolerance() || !$lineResult->isPriceWithinTolerance()) {
                return false;
            }
        }

        return true;
    }
}
