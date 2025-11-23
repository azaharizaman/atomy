<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * DuplicateEventException
 *
 * Thrown when attempting to append an event with an EventId that already exists.
 * This enforces idempotency in event appending.
 *
 * Requirements satisfied:
 * - REL-EVS-7405: Support idempotent event appending (duplicate detection via EventId)
 * - BUS-EVS-7103: Events are immutable - prevent duplicate events
 *
 * @package Nexus\EventStream\Exceptions
 */
final class DuplicateEventException extends EventStreamException
{
}
