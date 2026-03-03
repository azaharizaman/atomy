<?php

declare(strict_types=1);

namespace Nexus\ESG\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable value object for a 0-100 normalized sustainability score.
 */
final readonly class SustainabilityScore implements SerializableVO
{
    /**
     * @param float $value Normalized score between 0 and 100
     */
    public function __construct(
        public float $value
    ) {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Sustainability score must be between 0 and 100');
        }
    }

    /**
     * Get a qualitative grade based on the score.
     */
    public function getGrade(): string
    {
        return match (true) {
            $this->value >= 90 => 'A+',
            $this->value >= 80 => 'A',
            $this->value >= 70 => 'B',
            $this->value >= 60 => 'C',
            $this->value >= 50 => 'D',
            default => 'F',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'grade' => $this->getGrade(),
        ];
    }

    public function toString(): string
    {
        return sprintf('%.1f (%s)', $this->value, $this->getGrade());
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromArray(array $data): static
    {
        return new self((float)$data['value']);
    }
}
