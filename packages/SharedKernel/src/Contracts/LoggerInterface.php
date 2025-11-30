<?php

declare(strict_types=1);

namespace Nexus\SharedKernel\Contracts;

/**
 * Logger Interface
 *
 * Defines contract for logging within Nexus packages.
 * Compatible with PSR-3 LoggerInterface pattern.
 *
 * Consuming applications should bind to their logging implementation
 * (Monolog, Laravel Log, Symfony Logger, etc.).
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level Log level
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function log(string $level, string|\Stringable $message, array $context = []): void;
}
