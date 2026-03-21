<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;

final readonly class ClientKey
{
    public const MAX_LENGTH = 256;

    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw IdempotencyKeyInvalidException::forField('client_key', 'must not be empty');
        }
        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw IdempotencyKeyInvalidException::forField(
                'client_key',
                'exceeds maximum length of ' . (string) self::MAX_LENGTH,
            );
        }
        $this->value = $trimmed;
    }
}
