<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\ForecastScenario;

interface TreasuryForecastInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getScenario(): ForecastScenario;

    public function getForecastStartDate(): DateTimeImmutable;

    public function getForecastEndDate(): DateTimeImmutable;

    public function getOpeningBalance(): Money;

    public function getProjectedInflows(): Money;

    public function getProjectedOutflows(): Money;

    public function getClosingBalance(): Money;

    public function getMinimumBalance(): Money;

    public function getMaximumBalance(): Money;

    public function getConfidenceLevel(): float;

    public function getAssumptions(): array;

    public function getRiskFactors(): array;

    public function getCurrency(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function getNetCashFlow(): Money;
}
