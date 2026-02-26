<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Exceptions;

/**
 * AlertDispatchException
 *
 * Thrown when alert dispatching fails.
 *
 * @package Nexus\Telemetry\Exceptions
 */
final class AlertDispatchException extends MonitoringException
{
    public static function dispatchFailed(string $channel, string $reason, ?\Throwable $previous = null): self
    {
        $exception = new self(
            sprintf('Failed to dispatch alert via %s: %s', $channel, $reason),
            ['channel' => $channel, 'reason' => $reason]
        );
        
        // Manually set previous if provided (since parent constructor handles it differently)
        if ($previous !== null) {
            return new self(
                sprintf('Failed to dispatch alert via %s: %s', $channel, $reason),
                ['channel' => $channel, 'reason' => $reason, 'previous' => $previous->getMessage()],
                0,
                $previous
            );
        }
        
        return $exception;
    }

    public static function noChannelsConfigured(): self
    {
        return new self('No alert channels configured for dispatching');
    }
}
