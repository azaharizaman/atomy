<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use DateTimeImmutable;
use Nexus\Treasury\Contracts\TreasuryAnalyticsInterface;

final readonly class TreasuryKPIs implements TreasuryAnalyticsInterface
{
    private string $id;
    private string $tenantId;
    private DateTimeImmutable $calculatedAt;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?string $currency;

    public function __construct(
        ?string $id = null,
        ?string $tenantId = null,
        ?DateTimeImmutable $calculatedAt = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $currency = null,
        public float $daysCashOnHand = 0.0,
        public float $cashConversionCycle = 0.0,
        public float $daysSalesOutstanding = 0.0,
        public float $daysPayableOutstanding = 0.0,
        public float $daysInventoryOutstanding = 0.0,
        public float $quickRatio = 0.0,
        public float $currentRatio = 0.0,
        public float $workingCapitalRatio = 0.0,
        public float $liquidityScore = 0.0,
        public ?float $forecastAccuracy = null,
    ) {
        $this->id = $id ?? self::generateId();
        $this->tenantId = $tenantId ?? 'unknown';
        $this->calculatedAt = $calculatedAt ?? new DateTimeImmutable();
        $this->createdAt = $createdAt ?? $this->calculatedAt;
        $this->updatedAt = $updatedAt ?? $this->calculatedAt;
        $this->currency = $currency ?? 'USD';
    }

    public static function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-KPI-' . $uuid;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? self::generateId(),
            tenantId: $data['tenant_id'] ?? $data['tenantId'] ?? 'unknown',
            calculatedAt: isset($data['calculated_at'])
                ? new DateTimeImmutable($data['calculated_at'])
                : (isset($data['calculatedAt'])
                    ? new DateTimeImmutable($data['calculatedAt'])
                    : new DateTimeImmutable()),
            createdAt: isset($data['created_at'])
                ? new DateTimeImmutable($data['created_at'])
                : (isset($data['createdAt'])
                    ? new DateTimeImmutable($data['createdAt'])
                    : null),
            updatedAt: isset($data['updated_at'])
                ? new DateTimeImmutable($data['updated_at'])
                : (isset($data['updatedAt'])
                    ? new DateTimeImmutable($data['updatedAt'])
                    : null),
            currency: $data['currency'] ?? null,
            daysCashOnHand: (float) ($data['days_cash_on_hand'] ?? $data['daysCashOnHand'] ?? 0.0),
            cashConversionCycle: (float) ($data['cash_conversion_cycle'] ?? $data['cashConversionCycle'] ?? 0.0),
            daysSalesOutstanding: (float) ($data['days_sales_outstanding'] ?? $data['daysSalesOutstanding'] ?? 0.0),
            daysPayableOutstanding: (float) ($data['days_payable_outstanding'] ?? $data['daysPayableOutstanding'] ?? 0.0),
            daysInventoryOutstanding: (float) ($data['days_inventory_outstanding'] ?? $data['daysInventoryOutstanding'] ?? 0.0),
            quickRatio: (float) ($data['quick_ratio'] ?? $data['quickRatio'] ?? 0.0),
            currentRatio: (float) ($data['current_ratio'] ?? $data['currentRatio'] ?? 0.0),
            workingCapitalRatio: (float) ($data['working_capital_ratio'] ?? $data['workingCapitalRatio'] ?? 0.0),
            liquidityScore: (float) ($data['liquidity_score'] ?? $data['liquidityScore'] ?? 0.0),
            forecastAccuracy: isset($data['forecast_accuracy'])
                ? (float) $data['forecast_accuracy']
                : (isset($data['forecastAccuracy']) ? (float) $data['forecastAccuracy'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'calculatedAt' => $this->calculatedAt->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'currency' => $this->currency,
            'daysCashOnHand' => $this->daysCashOnHand,
            'cashConversionCycle' => $this->cashConversionCycle,
            'daysSalesOutstanding' => $this->daysSalesOutstanding,
            'daysPayableOutstanding' => $this->daysPayableOutstanding,
            'daysInventoryOutstanding' => $this->daysInventoryOutstanding,
            'quickRatio' => $this->quickRatio,
            'currentRatio' => $this->currentRatio,
            'workingCapitalRatio' => $this->workingCapitalRatio,
            'liquidityScore' => $this->liquidityScore,
            'forecastAccuracy' => $this->forecastAccuracy,
        ];
    }

    public function hasNegativeCycle(): bool
    {
        return $this->cashConversionCycle < 0;
    }

    public function hasHealthyLiquidity(): bool
    {
        return $this->quickRatio >= 1.0 && $this->currentRatio >= 1.5;
    }

    public function getOverallHealthScore(): float
    {
        $scores = [
            min($this->daysCashOnHand / 30, 1.0) * 100,
            min($this->liquidityScore, 100),
            $this->hasHealthyLiquidity() ? 100 : 50,
            $this->forecastAccuracy ?? 0,
        ];

        return array_sum($scores) / count($scores);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getCalculationDate(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function getDaysCashOnHand(): float
    {
        return $this->daysCashOnHand;
    }

    public function getCashConversionCycle(): float
    {
        return $this->cashConversionCycle;
    }

    public function getDaysSalesOutstanding(): float
    {
        return $this->daysSalesOutstanding;
    }

    public function getDaysPayableOutstanding(): float
    {
        return $this->daysPayableOutstanding;
    }

    public function getDaysInventoryOutstanding(): float
    {
        return $this->daysInventoryOutstanding;
    }

    public function getQuickRatio(): float
    {
        return $this->quickRatio;
    }

    public function getCurrentRatio(): float
    {
        return $this->currentRatio;
    }

    public function getWorkingCapitalRatio(): float
    {
        return $this->workingCapitalRatio;
    }

    public function getLiquidityScore(): float
    {
        return $this->liquidityScore;
    }

    public function getForecastAccuracy(): ?float
    {
        return $this->forecastAccuracy;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
