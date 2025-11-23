<?php

declare(strict_types=1);

namespace Nexus\EventStream\Testing;

use Nexus\EventStream\Contracts\EventInterface;

/**
 * Aggregate Tester Interface
 *
 * Framework-agnostic contract for testing event-sourced aggregates using the
 * Given-When-Then pattern. Enables deterministic, isolated testing of aggregate
 * business logic without infrastructure dependencies.
 *
 * Pattern:
 * - Given: Load aggregate with historical events (initial state)
 * - When: Execute a command that mutates the aggregate
 * - Then: Assert expected events were produced (or exception thrown)
 *
 * Example Usage:
 * ```php
 * $tester
 *     ->given([new AccountCreatedEvent('ACC-001', Money::of(1000, 'MYR'))])
 *     ->when(fn() => $aggregate->debit(Money::of(500, 'MYR')))
 *     ->then([new AccountDebitedEvent('ACC-001', Money::of(500, 'MYR'))]);
 * ```
 *
 * Requirements satisfied:
 * - USA-EVS-7709: Given-When-Then testing utilities
 * - FUN-EVS-7233: Aggregate testing framework-agnostic implementation
 * - ARC-EVS-7016: Testing utilities in package layer
 *
 * @package Nexus\EventStream\Testing
 */
interface AggregateTesterInterface
{
    /**
     * Set the aggregate's starting state by applying historical events.
     * This corresponds to the 'Given' clause in Given-When-Then.
     *
     * @param EventInterface[] $history Array of historical events
     * @return self Fluent interface
     */
    public function given(array $history): self;

    /**
     * Execute a command (callable) that mutates the aggregate.
     * This corresponds to the 'When' clause in Given-When-Then.
     *
     * The callable should:
     * 1. Execute aggregate business logic
     * 2. Return an array of new events produced
     * 3. Or throw an exception if command fails validation
     *
     * @param callable(): EventInterface[] $command Command to execute
     * @return self Fluent interface
     */
    public function when(callable $command): self;

    /**
     * Assert that the expected events were produced.
     * This corresponds to the 'Then' clause in Given-When-Then.
     *
     * Validation:
     * - Same number of events
     * - Same event types in same order
     * - Same event payloads (deep comparison)
     *
     * @param EventInterface[] $expectedEvents Expected events to compare
     * @return void
     * @throws \AssertionError If assertion fails
     */
    public function then(array $expectedEvents): void;

    /**
     * Assert that executing the command throws a specific exception.
     * Alternative to then() for testing validation failures.
     *
     * @param string $expectedExceptionClass Fully qualified exception class name
     * @return void
     * @throws \AssertionError If expected exception not thrown
     */
    public function thenThrows(string $expectedExceptionClass): void;
}
