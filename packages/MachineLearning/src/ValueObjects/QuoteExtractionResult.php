<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

final readonly class QuoteExtractionResult
{
    /**
     * @param array<int, array<string, mixed>> $extractedLines
     */
    public function __construct(
        public array $extractedLines,
        public float $confidence,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
