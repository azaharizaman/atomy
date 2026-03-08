<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\ValueObjects;

use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessResultInterface;

final readonly class ComparisonReadinessResult implements ComparisonReadinessResultInterface
{
    /**
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    public function __construct(
        private bool $ready,
        private bool $previewOnly,
        private array $blockers,
        private array $warnings,
    ) {
    }

    public static function pass(): self
    {
        return new self(ready: true, previewOnly: false, blockers: [], warnings: []);
    }

    /**
     * @param array<int, array{code: string, message: string}> $warnings
     */
    public static function previewAllowed(array $warnings): self
    {
        return new self(ready: true, previewOnly: true, blockers: [], warnings: $warnings);
    }

    /**
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    public static function blocked(array $blockers, array $warnings = []): self
    {
        return new self(ready: false, previewOnly: false, blockers: $blockers, warnings: $warnings);
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function isPreviewOnly(): bool
    {
        return $this->previewOnly;
    }

    public function getBlockers(): array
    {
        return $this->blockers;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
