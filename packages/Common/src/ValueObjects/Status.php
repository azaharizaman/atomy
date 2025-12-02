<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Enumable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Stateful;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable status value object with state transitions.
 * 
 * Used for Draft/Approved/Closed workflows and other state machines.
 */
final readonly class Status implements Stateful, Enumable, SerializableVO
{
    /**
     * @param string $state Current state
     * @param array<string> $allowedTransitions States this can transition to
     * @param bool $isFinal Whether this is a final state (cannot transition further)
     * @throws InvalidValueException
     */
    public function __construct(
        private string $state,
        private array $allowedTransitions = [],
        private bool $isFinal = false
    ) {
        if (empty(trim($state))) {
            throw new InvalidValueException('State is required');
        }
    }

    // Stateful implementation
    public function getState(): string
    {
        return $this->state;
    }

    public function canTransitionTo(Stateful $newState): bool
    {
        if (!$newState instanceof self) {
            return false;
        }

        if ($this->isFinal) {
            return false;
        }

        return in_array($newState->state, $this->allowedTransitions, true);
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    /**
     * Create new status with transition (validates transition is allowed).
     * 
     * @throws InvalidValueException
     */
    public function transitionTo(string $newState, array $allowedTransitions = [], bool $isFinal = false): self
    {
        $newStatus = new self($newState, $allowedTransitions, $isFinal);

        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidValueException(
                "Cannot transition from '{$this->state}' to '{$newState}'"
            );
        }

        return $newStatus;
    }

    // Enumable implementation
    public static function values(): array
    {
        // This would typically be defined by the domain context
        // Here we return a generic set
        return ['draft', 'pending', 'approved', 'rejected', 'closed'];
    }

    public function getValue(): string
    {
        return $this->state;
    }

    public function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'allowed_transitions' => $this->allowedTransitions,
            'is_final' => $this->isFinal,
        ];
    }

    public function toString(): string
    {
        return $this->state;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            state: $data['state'],
            allowedTransitions: $data['allowed_transitions'] ?? [],
            isFinal: $data['is_final'] ?? false
        );
    }

    // Common status factory methods
    public static function draft(): self
    {
        return new self('draft', ['pending', 'rejected'], false);
    }

    public static function pending(): self
    {
        return new self('pending', ['approved', 'rejected'], false);
    }

    public static function approved(): self
    {
        return new self('approved', ['closed'], false);
    }

    public static function rejected(): self
    {
        return new self('rejected', [], true);
    }

    public static function closed(): self
    {
        return new self('closed', [], true);
    }
}
