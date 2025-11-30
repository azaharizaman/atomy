<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * InvalidSnapshotException
 *
 * Thrown when a snapshot fails checksum validation.
 *
 * Requirements satisfied:
 * - REL-EVS-7406: Snapshots validated before use (checksum verification)
 * - REL-EVS-7407: Corrupted snapshots trigger automatic stream replay
 *
 * @package Nexus\EventStream\Exceptions
 */
class InvalidSnapshotException extends EventStreamException
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Invalid snapshot for aggregate "%s": %s',
                $aggregateId,
                $reason
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
