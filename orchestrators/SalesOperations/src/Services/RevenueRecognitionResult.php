<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class RevenueRecognitionResult
{
    public function __construct(
        public string $contractId,
        public float $totalTransactionPrice,
        public string $currencyCode,
        public array $performanceObligations,
        public array $allocation,
        public array $recognitionSchedule,
        public float $unrecognizedRevenue,
        public float $recognizedRevenue,
    ) {}

    public function isFullyRecognized(): bool
    {
        return $this->unrecognizedRevenue <= 0;
    }

    public function recognitionProgress(): float
    {
        if ($this->totalTransactionPrice <= 0) {
            return 0.0;
        }

        return ($this->recognizedRevenue / $this->totalTransactionPrice) * 100;
    }
}
