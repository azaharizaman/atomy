<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable circuit breaker state.
 */
final readonly class CircuitBreakerState
{
    /**
     * @param CircuitState $state Current state
     * @param int $failureCount Number of consecutive failures
     * @param \DateTimeImmutable|null $openedAt When circuit was opened
     * @param int $failureThreshold Number of failures before opening
     * @param int $timeoutSeconds How long to keep circuit open
     */
    public function __construct(
        public CircuitState $state,
        public int $failureCount,
        public ?\DateTimeImmutable $openedAt = null,
        public int $failureThreshold = 5,
        public int $timeoutSeconds = 60,
    ) {}

    /**
     * Create initial closed state.
     */
    public static function closed(int $failureThreshold = 5, int $timeoutSeconds = 60): self
    {
        return new self(
            state: CircuitState::CLOSED,
            failureCount: 0,
            openedAt: null,
            failureThreshold: $failureThreshold,
            timeoutSeconds: $timeoutSeconds
        );
    }

    /**
     * Check if circuit should allow requests.
     */
    public function allowsRequests(): bool
    {
        return match ($this->state) {
            CircuitState::CLOSED => true,
            CircuitState::OPEN => $this->shouldAttemptReset(),
            CircuitState::HALF_OPEN => true,
        };
    }

    /**
     * Check if enough time has passed to attempt reset.
     */
    public function shouldAttemptReset(): bool
    {
        if ($this->openedAt === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $elapsed = $now->getTimestamp() - $this->openedAt->getTimestamp();

        return $elapsed >= $this->timeoutSeconds;
    }

    /**
     * Record a failure and possibly open the circuit.
     */
    public function recordFailure(): self
    {
        $newFailureCount = $this->failureCount + 1;

        if ($newFailureCount >= $this->failureThreshold) {
            return new self(
                state: CircuitState::OPEN,
                failureCount: $newFailureCount,
                openedAt: new \DateTimeImmutable(),
                failureThreshold: $this->failureThreshold,
                timeoutSeconds: $this->timeoutSeconds
            );
        }

        return new self(
            state: $this->state,
            failureCount: $newFailureCount,
            openedAt: $this->openedAt,
            failureThreshold: $this->failureThreshold,
            timeoutSeconds: $this->timeoutSeconds
        );
    }

    /**
     * Record a success and possibly close the circuit.
     */
    public function recordSuccess(): self
    {
        return new self(
            state: CircuitState::CLOSED,
            failureCount: 0,
            openedAt: null,
            failureThreshold: $this->failureThreshold,
            timeoutSeconds: $this->timeoutSeconds
        );
    }

    /**
     * Transition to half-open state for testing.
     */
    public function halfOpen(): self
    {
        return new self(
            state: CircuitState::HALF_OPEN,
            failureCount: $this->failureCount,
            openedAt: $this->openedAt,
            failureThreshold: $this->failureThreshold,
            timeoutSeconds: $this->timeoutSeconds
        );
    }
}
