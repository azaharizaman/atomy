<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

use DateTimeImmutable;

/**
 * Clock Interface for time abstraction.
 *
 * **When to Use:**
 * Inject this interface whenever code needs the current time. This enables:
 * - Testability: Mock time in unit tests without system clock manipulation
 * - Consistency: Single source of truth for "now" across the application
 * - Time travel: Useful for testing time-dependent business logic
 *
 * **Expected Behavior:**
 * - now() MUST return a DateTimeImmutable (immutable for safety)
 * - Production implementations return system time
 * - Test implementations can return fixed or controlled time
 * - Multiple calls to now() within a transaction SHOULD return consistent time
 *
 * **Example Implementations:**
 *
 * Production (SystemClock):
 * ```php
 * final readonly class SystemClock implements ClockInterface
 * {
 *     public function now(): DateTimeImmutable
 *     {
 *         return new DateTimeImmutable();
 *     }
 * }
 * ```
 *
 * Testing (FrozenClock):
 * ```php
 * // Note: Intentionally NOT readonly to allow setTime() for testing.
 * // This deviates from the immutable-by-default pattern for test flexibility.
 * final class FrozenClock implements ClockInterface
 * {
 *     public function __construct(
 *         private DateTimeImmutable $frozenTime
 *     ) {}
 *
 *     public function now(): DateTimeImmutable
 *     {
 *         return $this->frozenTime;
 *     }
 *
 *     public function setTime(DateTimeImmutable $time): void
 *     {
 *         $this->frozenTime = $time;
 *     }
 * }
 * ```
 *
 * **Usage in Services:**
 * ```php
 * final readonly class InvoiceService
 * {
 *     public function __construct(
 *         private ClockInterface $clock
 *     ) {}
 *
 *     public function isOverdue(Invoice $invoice): bool
 *     {
 *         return $this->clock->now() > $invoice->getDueDate();
 *     }
 * }
 * ```
 *
 * **Anti-Pattern (DO NOT USE):**
 * ```php
 * // ❌ WRONG: Using now() helper or direct instantiation
 * $isOverdue = now() > $invoice->getDueDate();
 * $isOverdue = new \DateTime() > $invoice->getDueDate();
 * ```
 */
interface ClockInterface
{
    /**
     * Get the current time.
     *
     * @return DateTimeImmutable The current date and time (immutable)
     */
    public function now(): DateTimeImmutable;
}
