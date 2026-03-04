<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

use Nexus\Document\Contracts\AuditLogPayloadInterface;

/**
 * Immutable audit log payload implementation.
 */
final readonly class AuditLogPayload implements AuditLogPayloadInterface
{
    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(
        private string $logName,
        private string $description,
        private string $subjectType,
        private string $subjectId,
        private string $causerType,
        private string $causerId,
        private array $properties = [],
        private int $level = 1
    ) {
    }

    public function getLogName(): string { return $this->logName; }
    public function getDescription(): string { return $this->description; }
    public function getSubjectType(): string { return $this->subjectType; }
    public function getSubjectId(): string { return $this->subjectId; }
    public function getCauserType(): string { return $this->causerType; }
    public function getCauserId(): string { return $this->causerId; }
    public function getProperties(): array { return $this->properties; }
    public function getLevel(): int { return $this->level; }
}
