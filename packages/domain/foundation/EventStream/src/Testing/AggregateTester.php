<?php

declare(strict_types=1);

namespace Nexus\EventStream\Testing;

use Nexus\EventStream\Contracts\EventInterface;

/**
 * Aggregate Scenario Tester
 *
 * Framework-agnostic implementation of Given-When-Then pattern for testing
 * event-sourced aggregates in isolation without infrastructure dependencies.
 *
 * This tester enables deterministic testing by:
 * 1. Loading aggregate state from historical events (Given)
 * 2. Executing a command that may mutate the aggregate (When)
 * 3. Asserting expected events were produced or exception thrown (Then)
 *
 * Example Usage:
 * ```php
 * $tester = new AggregateTester();
 * $tester
 *     ->given([new AccountCreatedEvent('ACC-001', Money::of(1000, 'MYR'))])
 *     ->when(fn() => $aggregate->debit(Money::of(500, 'MYR')))
 *     ->then([new AccountDebitedEvent('ACC-001', Money::of(500, 'MYR'))]);
 * ```
 *
 * Requirements satisfied:
 * - USA-EVS-7709: Given-When-Then testing utilities
 * - FUN-EVS-7233: Framework-agnostic aggregate testing
 * - ARC-EVS-7016: Testing utilities in package layer
 *
 * @package Nexus\EventStream\Testing
 */
final class AggregateTester implements AggregateTesterInterface
{
    /**
     * @var EventInterface[]
     */
    private array $history = [];

    /**
     * @var EventInterface[]|null
     */
    private ?array $producedEvents = null;

    private ?\Throwable $thrownException = null;

    private bool $whenExecuted = false;

    public function given(array $history): self
    {
        $this->history = $history;
        $this->producedEvents = null;
        $this->thrownException = null;
        $this->whenExecuted = false;

        return $this;
    }

    public function when(callable $command): self
    {
        $this->producedEvents = null;
        $this->thrownException = null;
        $this->whenExecuted = true;

        try {
            $this->producedEvents = $command();
        } catch (\Throwable $e) {
            $this->thrownException = $e;
        }

        return $this;
    }

    public function then(array $expectedEvents): void
    {
        if (!$this->whenExecuted) {
            throw new \LogicException('Cannot call then() before when()');
        }

        if ($this->thrownException !== null) {
            throw new \AssertionError(
                sprintf(
                    'Expected events but command threw exception: %s',
                    $this->thrownException->getMessage()
                )
            );
        }

        $actualCount = count($this->producedEvents ?? []);
        $expectedCount = count($expectedEvents);

        if ($actualCount !== $expectedCount) {
            throw new \AssertionError(
                sprintf('Expected %d events, but got %d', $expectedCount, $actualCount)
            );
        }

        foreach ($expectedEvents as $index => $expectedEvent) {
            $actualEvent = $this->producedEvents[$index];

            $this->assertEventTypesMatch($expectedEvent, $actualEvent, $index);
            $this->assertEventPayloadsMatch($expectedEvent, $actualEvent, $index);
        }
    }

    public function thenThrows(string $expectedExceptionClass): void
    {
        if (!$this->whenExecuted) {
            throw new \LogicException('Cannot call thenThrows() before when()');
        }

        if ($this->thrownException === null) {
            throw new \AssertionError(
                sprintf('Expected exception %s was not thrown', $expectedExceptionClass)
            );
        }

        $actualExceptionClass = get_class($this->thrownException);

        if (!is_a($this->thrownException, $expectedExceptionClass)) {
            throw new \AssertionError(
                sprintf(
                    'Expected exception %s but got %s',
                    $expectedExceptionClass,
                    $actualExceptionClass
                )
            );
        }
    }

    /**
     * Assert that event types match
     */
    private function assertEventTypesMatch(
        EventInterface $expected,
        EventInterface $actual,
        int $index
    ): void {
        $expectedType = $expected->getEventType();
        $actualType = $actual->getEventType();

        if ($expectedType !== $actualType) {
            throw new \AssertionError(
                sprintf(
                    'Event type mismatch at index %d: expected %s but got %s',
                    $index,
                    $expectedType,
                    $actualType
                )
            );
        }
    }

    /**
     * Assert that event payloads match (deep comparison)
     */
    private function assertEventPayloadsMatch(
        EventInterface $expected,
        EventInterface $actual,
        int $index
    ): void {
        $expectedPayload = $expected->getPayload();
        $actualPayload = $actual->getPayload();

        if ($expectedPayload !== $actualPayload) {
            throw new \AssertionError(
                sprintf(
                    'Event payload mismatch at index %d',
                    $index
                )
            );
        }
    }
}
