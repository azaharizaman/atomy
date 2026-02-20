<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface TreasuryDashboardInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getDashboardDate(): DateTimeImmutable;

    public function getTotalCashPosition(): Money;

    public function getAvailableCashBalance(): Money;

    public function getInvestedCashBalance(): Money;

    public function getReservedCashBalance(): Money;

    public function getProjectedCashFlowToday(): Money;

    public function getProjectedCashFlowWeek(): Money;

    public function getProjectedCashFlowMonth(): Money;

    public function getDaysCashOnHand(): float;

    public function getCashConversionCycle(): float;

    public function getPendingApprovalsCount(): int;

    public function getActiveInvestmentsCount(): int;

    public function getActiveIntercompanyLoansCount(): int;

    public function getAlerts(): array;

    public function getKpiSummary(): array;

    public function getCurrency(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
