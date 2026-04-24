<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Exceptions;

use InvalidArgumentException;

final class ProcurementMlContractException extends InvalidArgumentException
{
    public static function invalidValue(string $subject): self
    {
        return new self(sprintf('Invalid procurement ML %s.', $subject));
    }

    /**
     * @param list<string> $fields
     */
    public static function missingFields(string $subject, array $fields): self
    {
        sort($fields);

        return new self(sprintf(
            'Procurement ML %s is missing required fields: %s.',
            $subject,
            implode(', ', $fields),
        ));
    }
}
