<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Immutable result of a comparison readiness check.
 */
interface ComparisonReadinessResultInterface
{
    /**
     * Whether the comparison run is allowed to proceed.
     */
    public function isReady(): bool;

    /**
     * Whether the result permits only a preview (draft) run.
     */
    public function isPreviewOnly(): bool;

    /**
     * Blocking violations that prevent any run.
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function getBlockers(): array;

    /**
     * Non-blocking warnings (e.g., low confidence lines).
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function getWarnings(): array;
}
