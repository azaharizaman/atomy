<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;

final readonly class RequestFingerprint
{
    public const MAX_LENGTH = 512;

    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw IdempotencyKeyInvalidException::forField('fingerprint', 'must not be empty');
        }
        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw IdempotencyKeyInvalidException::forField(
                'fingerprint',
                'exceeds maximum length of ' . (string) self::MAX_LENGTH,
            );
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
