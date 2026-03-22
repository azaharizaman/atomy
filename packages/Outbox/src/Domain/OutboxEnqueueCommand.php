<?php

declare(strict_types=1);

namespace Nexus\Outbox\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\TenantId;

/**
 * @phpstan-type PayloadArray array<string, mixed>
 */
final readonly class OutboxEnqueueCommand
{
    private const MAX_CORRELATION_CAUSATION_LENGTH = 255;

    public readonly ?string $correlationId;

    public readonly ?string $causationId;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public TenantId $tenantId,
        public DedupKey $dedupKey,
        public EventTypeRef $eventType,
        public array $payload,
        public array $metadata,
        ?string $correlationId,
        ?string $causationId,
        public DateTimeImmutable $createdAt,
    ) {
        $this->correlationId = self::normalizeOptionalStringField(
            $correlationId,
            'correlationId',
        );
        $this->causationId = self::normalizeOptionalStringField(
            $causationId,
            'causationId',
        );
    }

    private static function normalizeOptionalStringField(?string $value, string $parameterName): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $trimmed) === 1) {
            throw new InvalidArgumentException(
                $parameterName . ' must not contain control characters.',
            );
        }

        $len = mb_strlen($trimmed, 'UTF-8');
        if ($len > self::MAX_CORRELATION_CAUSATION_LENGTH) {
            throw new InvalidArgumentException(
                $parameterName . ' exceeds maximum length of ' . (string) self::MAX_CORRELATION_CAUSATION_LENGTH . ' characters.',
            );
        }

        return $trimmed;
    }
}
