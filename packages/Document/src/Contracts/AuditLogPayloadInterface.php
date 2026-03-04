<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Immutable payload for audit log entries.
 */
interface AuditLogPayloadInterface
{
    public function getLogName(): string;
    public function getDescription(): string;
    public function getSubjectType(): string;
    public function getSubjectId(): string;
    public function getCauserType(): string;
    public function getCauserId(): string;
    /** @return array<string, mixed> */
    public function getProperties(): array;
    public function getLevel(): int;
}
