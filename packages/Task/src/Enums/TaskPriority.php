<?php

declare(strict_types=1);

namespace Nexus\Task\Enums;

/**
 * Task priority (FUN-PRO-0565).
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function sortOrder(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 1,
            self::High => 2,
            self::Critical => 3,
        };
    }
}
