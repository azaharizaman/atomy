<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Exceptions;

final class UnsupportedRfqBulkActionException extends \RuntimeException
{
    /**
     * @param array<string> $allowedActions
     */
    public static function fromAction(string $action, array $allowedActions = []): self
    {
        $suffix = $allowedActions === [] ? '' : sprintf(' Allowed actions: %s.', implode(', ', $allowedActions));

        return new self(sprintf(
            'Unsupported RFQ bulk action "%s".%s',
            trim($action),
            $suffix,
        ));
    }
}
