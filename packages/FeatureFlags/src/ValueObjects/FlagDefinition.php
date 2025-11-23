<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\ValueObjects;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Exceptions\InvalidFlagDefinitionException;

/**
 * Immutable value object representing a feature flag definition.
 *
 * This class enforces:
 * - Strict name validation (lowercase, alphanumeric, dots, underscores, max 100 chars)
 * - Value type matching for each strategy
 * - Deterministic checksum calculation for cache validation
 */
final readonly class FlagDefinition implements FlagDefinitionInterface
{
    /**
     * Create a new flag definition.
     *
     * @param string $name Flag name (must match /^[a-z0-9_\.]{1,100}$/)
     * @param bool $enabled Base enabled state
     * @param FlagStrategy $strategy Evaluation strategy
     * @param mixed $value Strategy-specific value
     * @param FlagOverride|null $override Force ON/OFF override
     * @param array<string, mixed> $metadata Additional metadata
     * @throws InvalidFlagDefinitionException If validation fails
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private FlagStrategy $strategy,
        private mixed $value,
        private ?FlagOverride $override = null,
        private array $metadata = []
    ) {
        $this->validateName();
        $this->validateValueType();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getStrategy(): FlagStrategy
    {
        return $this->strategy;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOverride(): ?FlagOverride
    {
        return $this->override;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getChecksum(): string
    {
        return $this->calculateChecksum();
    }

    /**
     * Validate the flag name against the required pattern.
     *
     * @throws InvalidFlagDefinitionException If name is invalid
     */
    private function validateName(): void
    {
        if (!preg_match('/^[a-z0-9_\.]{1,100}$/', $this->name)) {
            throw InvalidFlagDefinitionException::invalidName(
                $this->name,
                'Flag name must match pattern: /^[a-z0-9_\.]{1,100}$/ (lowercase, alphanumeric, dots, underscores, max 100 chars)'
            );
        }

        if ($this->name === '') {
            throw InvalidFlagDefinitionException::invalidName(
                $this->name,
                'Flag name cannot be empty'
            );
        }

        if (strlen($this->name) > 100) {
            throw InvalidFlagDefinitionException::invalidName(
                $this->name,
                'Flag name cannot exceed 100 characters'
            );
        }
    }

    /**
     * Validate that the value type matches the strategy requirements.
     *
     * @throws InvalidFlagDefinitionException If value type is invalid
     */
    private function validateValueType(): void
    {
        match ($this->strategy) {
            FlagStrategy::SYSTEM_WIDE => $this->validateSystemWideValue(),
            FlagStrategy::PERCENTAGE_ROLLOUT => $this->validatePercentageValue(),
            FlagStrategy::TENANT_LIST, FlagStrategy::USER_LIST => $this->validateListValue(),
            FlagStrategy::CUSTOM => $this->validateCustomValue(),
        };
    }

    private function validateSystemWideValue(): void
    {
        if ($this->value !== null) {
            throw InvalidFlagDefinitionException::invalidValueType(
                $this->strategy,
                'null',
                gettype($this->value)
            );
        }
    }

    private function validatePercentageValue(): void
    {
        if (!is_int($this->value)) {
            throw InvalidFlagDefinitionException::invalidValueType(
                $this->strategy,
                'int',
                gettype($this->value)
            );
        }

        if ($this->value < 0 || $this->value > 100) {
            throw InvalidFlagDefinitionException::invalidPercentageRange($this->value);
        }
    }

    private function validateListValue(): void
    {
        if (!is_array($this->value)) {
            throw InvalidFlagDefinitionException::invalidValueType(
                $this->strategy,
                'array',
                gettype($this->value)
            );
        }

        foreach ($this->value as $item) {
            if (!is_string($item)) {
                throw InvalidFlagDefinitionException::invalidListItem($this->strategy, gettype($item));
            }
        }
    }

    private function validateCustomValue(): void
    {
        if (!is_string($this->value)) {
            throw InvalidFlagDefinitionException::invalidValueType(
                $this->strategy,
                'class-string',
                gettype($this->value)
            );
        }

        if (!class_exists($this->value)) {
            throw InvalidFlagDefinitionException::customClassNotFound($this->value);
        }
    }

    /**
     * Calculate a deterministic checksum of the flag's state.
     *
     * Used for cache validation to detect stale cached definitions.
     *
     * @return string SHA-256 hash
     */
    private function calculateChecksum(): string
    {
        $data = [
            $this->enabled,
            $this->strategy->value,
            $this->value,
            $this->override?->value,
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }
}
