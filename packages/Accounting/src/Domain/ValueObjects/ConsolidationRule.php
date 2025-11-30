<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a consolidation rule for eliminations.
 */
final readonly class ConsolidationRule
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $code,
        private string $name,
        private string $type,
        private array $config = []
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Create an intercompany elimination rule.
     */
    public static function intercompanyElimination(string $code, string $name): self
    {
        return new self($code, $name, 'intercompany_elimination', [
            'eliminate_receivables' => true,
            'eliminate_payables' => true,
            'eliminate_revenue' => true,
            'eliminate_expense' => true,
        ]);
    }

    /**
     * Create an investment elimination rule.
     */
    public static function investmentElimination(string $code, string $name): self
    {
        return new self($code, $name, 'investment_elimination', [
            'eliminate_investment' => true,
            'eliminate_equity' => true,
        ]);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'config' => $this->config,
        ];
    }
}
