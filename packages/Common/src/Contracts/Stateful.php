<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing state with transition rules.
 *
 * Provides a contract for state machine value objects that track current state
 * and enforce valid state transitions. This is for simple, self-contained state
 * tracking within value objects.
 *
 * **When to Use:**
 * - Simple status tracking within value objects (OrderStatus, InvoiceStatus)
 * - When state transition rules are intrinsic to the value object
 * - Embedding lightweight state machine behavior in VOs
 *
 * **When to Use Nexus\Workflow Instead:**
 * - Complex state machines with many transitions
 * - When state transitions trigger side effects
 * - When you need transition history/audit
 * - Multi-step approval workflows
 *
 * **Expected Behavior:**
 * - `getState()` returns the current state identifier string
 * - `canTransitionTo()` validates if transition is allowed
 * - `isFinal()` returns true for terminal states (no further transitions)
 * - State transitions should be implemented via factory methods
 *
 * @example Example implementation for InvoiceStatus:
 * ```php
 * final readonly class InvoiceStatus implements Stateful
 * {
 *     private const TRANSITIONS = [
 *         'draft'     => ['pending', 'cancelled'],
 *         'pending'   => ['paid', 'overdue', 'cancelled'],
 *         'overdue'   => ['paid', 'written_off'],
 *         'paid'      => [],  // Final state
 *         'cancelled' => [],  // Final state
 *         'written_off' => [], // Final state
 *     ];
 *
 *     private function __construct(private string $state) {}
 *
 *     public static function draft(): self { return new self('draft'); }
 *     public static function pending(): self { return new self('pending'); }
 *     public static function paid(): self { return new self('paid'); }
 *
 *     public function getState(): string { return $this->state; }
 *
 *     public function canTransitionTo(Stateful $newState): bool
 *     {
 *         $allowed = self::TRANSITIONS[$this->state] ?? [];
 *         return in_array($newState->getState(), $allowed, true);
 *     }
 *
 *     public function isFinal(): bool
 *     {
 *         return empty(self::TRANSITIONS[$this->state] ?? []);
 *     }
 *
 *     public function transitionTo(self $newState): self
 *     {
 *         if (!$this->canTransitionTo($newState)) {
 *             throw new InvalidStateTransitionException(
 *                 "Cannot transition from {$this->state} to {$newState->state}"
 *             );
 *         }
 *         return $newState;
 *     }
 * }
 * ```
 */
interface Stateful
{
    /**
     * Get the current state value.
     */
    public function getState(): string;

    /**
     * Check if transition to another state is allowed.
     */
    public function canTransitionTo(self $newState): bool;

    /**
     * Check if this is a final state.
     */
    public function isFinal(): bool;
}
