<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Exceptions;

use RuntimeException;

/**
 * Thrown when a pipeline step fails
 */
final class PipelineStepException extends RuntimeException
{
    public function __construct(
        string $pipeline,
        string $step,
        ?string $reason = null
    ) {
        $message = "Pipeline {$pipeline} failed at step {$step}";
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }
}
