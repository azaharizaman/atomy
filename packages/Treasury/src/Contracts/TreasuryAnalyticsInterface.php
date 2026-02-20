<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;

interface TreasuryAnalyticsInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCalculationDate(): DateTimeImmutable;

    public function getDaysCashOnHand(): float;

    public function getCashConversionCycle(): float;

    public function getDaysSalesOutstanding(): float;

    public function getDaysPayableOutstanding(): float;

    public function getDaysInventoryOutstanding(): float;

    public function getQuickRatio(): float;

    public function getCurrentRatio(): float;

    public function getWorkingCapitalRatio(): float;

    public function getLiquidityScore(): float;

    public function getForecastAccuracy(): ?float;

    public function getCurrency(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
