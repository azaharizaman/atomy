<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;

interface WorkingCapitalOptimizerInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCalculationDate(): DateTimeImmutable;

    public function getDaysSalesOutstanding(): float;

    public function getDaysPayableOutstanding(): float;

    public function getDaysInventoryOutstanding(): float;

    public function getCashConversionCycle(): float;

    public function getWorkingCapital(): float;

    public function getWorkingCapitalRatio(): float;

    public function getOptimizationOpportunities(): array;

    public function getRecommendations(): array;

    public function getCurrency(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function hasNegativeCycle(): bool;
}
