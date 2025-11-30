<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use DateTimeImmutable;

/**
 * Forecast Result Value Object
 *
 * Persistable output from a cash flow forecast run.
 */
final readonly class ForecastResultVO
{
    /**
     * @param array<string, string> $dailyBalances Array of date => balance
     */
    public function __construct(
        private ScenarioParametersVO $parameters,
        private array $dailyBalances,
        private DateTimeImmutable $generatedAt,
        private string $generatedBy
    ) {
    }

    public function getParameters(): ScenarioParametersVO
    {
        return $this->parameters;
    }

    /**
     * @return array<string, string>
     */
    public function getDailyBalances(): array
    {
        return $this->dailyBalances;
    }

    public function getGeneratedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function getGeneratedBy(): string
    {
        return $this->generatedBy;
    }

    /**
     * Get minimum projected balance
     */
    public function getMinimumBalance(): string
    {
        if (empty($this->dailyBalances)) {
            return '0.0000';
        }

        $min = reset($this->dailyBalances);
        foreach ($this->dailyBalances as $balance) {
            if (bccomp($balance, $min, 4) < 0) {
                $min = $balance;
            }
        }
        return $min;
    }

    /**
     * Get maximum projected balance
     */
    public function getMaximumBalance(): string
    {
        if (empty($this->dailyBalances)) {
            return '0.0000';
        }

        $max = reset($this->dailyBalances);
        foreach ($this->dailyBalances as $balance) {
            if (bccomp($balance, $max, 4) > 0) {
                $max = $balance;
            }
        }
        return $max;
    }

    /**
     * Check if any day has negative balance
     */
    public function hasNegativeBalance(): bool
    {
        foreach ($this->dailyBalances as $balance) {
            if (bccomp($balance, '0', 4) < 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'parameters' => $this->parameters->toArray(),
            'daily_balances' => $this->dailyBalances,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'generated_by' => $this->generatedBy,
            'min_balance' => $this->getMinimumBalance(),
            'max_balance' => $this->getMaximumBalance(),
            'has_negative' => $this->hasNegativeBalance(),
        ];
    }
}
