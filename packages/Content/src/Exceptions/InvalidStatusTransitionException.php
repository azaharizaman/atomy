<?php

declare(strict_types=1);

namespace Nexus\Content\Exceptions;

use Nexus\Content\Enums\ContentStatus;

/**
 * Thrown when invalid status transition is attempted
 */
class InvalidStatusTransitionException extends ContentException
{
    public static function fromTo(ContentStatus $from, ContentStatus $to): self
    {
        return new self(
            sprintf(
                'Invalid status transition from %s to %s',
                $from->value,
                $to->value
            )
        );
    }
}
