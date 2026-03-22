<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Internal\BoundedStringValidator;

final readonly class OperationRef
{
    public const MAX_LENGTH = 128;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'operation');
    }
}
