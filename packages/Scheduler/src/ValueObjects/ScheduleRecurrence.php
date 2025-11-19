<?php

declare(strict_types=1);

namespace Nexus\Scheduler\ValueObjects;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\RecurrenceType;

/**
 * Schedule Recurrence Value Object
 *
 * Defines how a scheduled job should repeat.
 * Immutable value object with recurrence calculation logic.
 */
final readonly class ScheduleRecurrence
{
    /**
     * @param RecurrenceType $type Recurrence pattern type
     * @param int $interval Interval multiplier (e.g., every 2 days)
     * @param string|null $cronExpression Cron expression (required if type is CRON)
     * @param DateTimeImmutable|null $endsAt Optional end date for recurrence
     * @param int|null $maxOccurrences Optional maximum number of occurrences
     */
    public function __construct(
        public RecurrenceType $type,
        public int $interval = 1,
        public ?string $cronExpression = null,
        public ?DateTimeImmutable $endsAt = null,
        public ?int $maxOccurrences = null,
    ) {
        if ($interval < 1) {
            throw new \InvalidArgumentException('Recurrence interval must be at least 1');
        }
        
        if ($type->requiresCronExpression() && $cronExpression === null) {
            throw new \InvalidArgumentException('Cron expression is required for CRON recurrence type');
        }
        
        if (!$type->requiresCronExpression() && $cronExpression !== null) {
            throw new \InvalidArgumentException("Cron expression is only valid for CRON recurrence type");
        }
        
        if ($maxOccurrences !== null && $maxOccurrences < 1) {
            throw new \InvalidArgumentException('Maximum occurrences must be at least 1');
        }
    }
    
    /**
     * Create a one-time (non-recurring) schedule
     */
    public static function once(): self
    {
        return new self(type: RecurrenceType::ONCE);
    }
    
    /**
     * Create a daily recurrence
     */
    public static function daily(int $interval = 1): self
    {
        return new self(type: RecurrenceType::DAILY, interval: $interval);
    }
    
    /**
     * Create a weekly recurrence
     */
    public static function weekly(int $interval = 1): self
    {
        return new self(type: RecurrenceType::WEEKLY, interval: $interval);
    }
    
    /**
     * Create a monthly recurrence
     */
    public static function monthly(int $interval = 1): self
    {
        return new self(type: RecurrenceType::MONTHLY, interval: $interval);
    }
    
    /**
     * Create a cron-based recurrence
     */
    public static function cron(string $expression): self
    {
        return new self(type: RecurrenceType::CRON, cronExpression: $expression);
    }
    
    /**
     * Check if this schedule repeats
     */
    public function isRepeating(): bool
    {
        return $this->type->isRepeating();
    }
    
    /**
     * Check if the recurrence has ended
     */
    public function hasEnded(DateTimeImmutable $asOf, int $occurrenceCount): bool
    {
        if ($this->endsAt !== null && $asOf > $this->endsAt) {
            return true;
        }
        
        if ($this->maxOccurrences !== null && $occurrenceCount >= $this->maxOccurrences) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the interval in seconds
     */
    public function getIntervalSeconds(): ?int
    {
        $baseInterval = $this->type->getBaseIntervalSeconds();
        
        if ($baseInterval === null) {
            return null;
        }
        
        return $baseInterval * $this->interval;
    }
    
    /**
     * Get a human-readable description
     */
    public function describe(): string
    {
        if ($this->type === RecurrenceType::ONCE) {
            return 'One time';
        }
        
        if ($this->type === RecurrenceType::CRON) {
            return "Cron: {$this->cronExpression}";
        }
        
        $description = $this->interval === 1
            ? $this->type->label()
            : "Every {$this->interval} " . strtolower($this->type->label());
        
        if ($this->endsAt !== null) {
            $description .= " (ends " . $this->endsAt->format('Y-m-d') . ")";
        } elseif ($this->maxOccurrences !== null) {
            $description .= " (max {$this->maxOccurrences} times)";
        }
        
        return $description;
    }
    
    /**
     * Convert to array for serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'interval' => $this->interval,
            'cronExpression' => $this->cronExpression,
            'endsAt' => $this->endsAt?->format('c'),
            'maxOccurrences' => $this->maxOccurrences,
        ];
    }
    
    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: RecurrenceType::from($data['type']),
            interval: $data['interval'] ?? 1,
            cronExpression: $data['cronExpression'] ?? null,
            endsAt: isset($data['endsAt']) ? new DateTimeImmutable($data['endsAt']) : null,
            maxOccurrences: $data['maxOccurrences'] ?? null,
        );
    }
}
