<?php

declare(strict_types=1);

namespace Nexus\PDPA\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\PDPA\Exceptions\PdpaException;

/**
 * PDPA-specific deadline calculations.
 *
 * Malaysia PDPA 2010:
 * - Section 30: Data subject access requests must be responded to within 21 days
 * - Extensions: Commissioner may grant extensions for complex requests
 * - Breach notification: No specific timeline (unlike GDPR), but best practice is 72 hours
 *
 * Key differences from GDPR:
 * - 21-day response deadline (vs 30 days in GDPR)
 * - No statutory extension period (vs 60 days in GDPR)
 * - Breach notification not mandatory by law
 */
final class PdpaDeadline
{
    /**
     * Standard PDPA response deadline: 21 days (Section 30).
     */
    public const STANDARD_DEADLINE_DAYS = 21;

    /**
     * Maximum extension period (when granted by Commissioner).
     */
    public const MAX_EXTENSION_DAYS = 14;

    /**
     * Recommended breach notification timeline (best practice).
     */
    public const RECOMMENDED_BREACH_NOTIFICATION_HOURS = 72;

    private function __construct(
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $deadlineDate,
        public readonly int $standardDays,
        public readonly bool $isExtended = false,
        public readonly ?DateTimeImmutable $originalDeadline = null,
        public readonly ?string $extensionReason = null,
    ) {}

    /**
     * Create deadline for a data subject access request (Section 30).
     */
    public static function forAccessRequest(DataSubjectRequest $request): self
    {
        $startDate = $request->submittedAt;
        $deadlineDate = $startDate->modify('+' . self::STANDARD_DEADLINE_DAYS . ' days');

        return new self(
            startDate: $startDate,
            deadlineDate: $deadlineDate,
            standardDays: self::STANDARD_DEADLINE_DAYS,
            isExtended: false,
            originalDeadline: null,
            extensionReason: null,
        );
    }

    /**
     * Create deadline for data subject request (any type).
     */
    public static function forDataSubjectRequest(DataSubjectRequest $request): self
    {
        return self::forAccessRequest($request);
    }

    /**
     * Create deadline for breach notification (best practice).
     *
     * Note: PDPA 2010 does not mandate breach notification timelines,
     * but 72 hours is industry best practice aligned with GDPR.
     */
    public static function forBreachNotification(DateTimeImmutable $discoveredAt): self
    {
        $deadlineDate = $discoveredAt->modify('+' . self::RECOMMENDED_BREACH_NOTIFICATION_HOURS . ' hours');

        return new self(
            startDate: $discoveredAt,
            deadlineDate: $deadlineDate,
            standardDays: 3, // 72 hours = 3 days
            isExtended: false,
            originalDeadline: null,
            extensionReason: null,
        );
    }

    /**
     * Create custom deadline.
     */
    public static function create(
        DateTimeImmutable $startDate,
        int $days,
        bool $isExtended = false,
        ?DateTimeImmutable $originalDeadline = null,
        ?string $extensionReason = null,
    ): self {
        $deadlineDate = $startDate->modify('+' . $days . ' days');

        return new self(
            startDate: $startDate,
            deadlineDate: $deadlineDate,
            standardDays: $days,
            isExtended: $isExtended,
            originalDeadline: $originalDeadline,
            extensionReason: $extensionReason,
        );
    }

    /**
     * Extend deadline (requires Commissioner approval for PDPA).
     *
     * @throws PdpaException If already extended
     */
    public function extend(string $reason, int $days = self::MAX_EXTENSION_DAYS): self
    {
        if ($this->isExtended) {
            throw PdpaException::extensionLimitExceeded();
        }

        if ($days > self::MAX_EXTENSION_DAYS) {
            throw PdpaException::extensionTooLong($days, self::MAX_EXTENSION_DAYS);
        }

        $newDeadline = $this->deadlineDate->modify('+' . $days . ' days');

        return new self(
            startDate: $this->startDate,
            deadlineDate: $newDeadline,
            standardDays: $this->standardDays,
            isExtended: true,
            originalDeadline: $this->deadlineDate,
            extensionReason: $reason,
        );
    }

    /**
     * Check if extension is possible.
     */
    public function canExtend(): bool
    {
        return !$this->isExtended;
    }

    /**
     * Check if deadline has passed.
     */
    public function isOverdue(?DateTimeImmutable $asOf = null): bool
    {
        $checkDate = $asOf ?? new DateTimeImmutable();

        return $checkDate > $this->deadlineDate;
    }

    /**
     * Get days remaining until deadline.
     *
     * Returns negative value if overdue.
     */
    public function getDaysRemaining(?DateTimeImmutable $asOf = null): int
    {
        $checkDate = $asOf ?? new DateTimeImmutable();

        $diff = $checkDate->diff($this->deadlineDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Get days overdue.
     *
     * Returns 0 if not overdue.
     */
    public function getDaysOverdue(?DateTimeImmutable $asOf = null): int
    {
        $remaining = $this->getDaysRemaining($asOf);

        return $remaining < 0 ? abs($remaining) : 0;
    }

    /**
     * Check if breach notification is overdue (best practice timeline).
     */
    public function isBreachNotificationOverdue(?DateTimeImmutable $asOf = null): bool
    {
        return $this->isOverdue($asOf);
    }

    /**
     * Get hours remaining for breach notification.
     */
    public function getHoursRemaining(?DateTimeImmutable $asOf = null): int
    {
        $checkDate = $asOf ?? new DateTimeImmutable();

        $diff = $checkDate->diff($this->deadlineDate);
        $hours = ($diff->days * 24) + $diff->h;

        return $diff->invert ? -$hours : $hours;
    }

    /**
     * Get progress percentage towards deadline (0-100).
     */
    public function getProgressPercentage(?DateTimeImmutable $asOf = null): float
    {
        $checkDate = $asOf ?? new DateTimeImmutable();

        $totalSeconds = $this->deadlineDate->getTimestamp() - $this->startDate->getTimestamp();
        $elapsedSeconds = $checkDate->getTimestamp() - $this->startDate->getTimestamp();

        if ($totalSeconds <= 0) {
            return 100.0;
        }

        $percentage = ($elapsedSeconds / $totalSeconds) * 100;

        return min(100.0, max(0.0, $percentage));
    }

    /**
     * Get urgency level.
     *
     * @return string 'critical' (overdue), 'high' (<3 days), 'medium' (<7 days), 'low' (>7 days)
     */
    public function getUrgencyLevel(?DateTimeImmutable $asOf = null): string
    {
        $daysRemaining = $this->getDaysRemaining($asOf);

        if ($daysRemaining < 0) {
            return 'critical';
        }

        if ($daysRemaining <= 3) {
            return 'high';
        }

        if ($daysRemaining <= 7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get formatted deadline string.
     */
    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->deadlineDate->format($format);
    }

    /**
     * Get the deadline date.
     */
    public function getDeadlineDate(): DateTimeImmutable
    {
        return $this->deadlineDate;
    }

    /**
     * Get the start date.
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * Check if deadline has been extended.
     */
    public function isExtended(): bool
    {
        return $this->isExtended;
    }

    /**
     * Get extension reason if extended.
     */
    public function getExtensionReason(): ?string
    {
        return $this->extensionReason;
    }

    /**
     * Get the regulation name.
     */
    public function getRegulation(): string
    {
        return 'PDPA 2010';
    }

    /**
     * Get standard deadline days.
     */
    public function getStandardDeadlineDays(): int
    {
        return self::STANDARD_DEADLINE_DAYS;
    }

    /**
     * Get maximum extension days.
     */
    public function getMaxExtensionDays(): int
    {
        return self::MAX_EXTENSION_DAYS;
    }

    /**
     * Create deadline from a specific date.
     */
    public static function fromDate(DateTimeImmutable $startDate): self
    {
        return self::create($startDate, self::STANDARD_DEADLINE_DAYS);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'deadline_date' => $this->deadlineDate->format('Y-m-d H:i:s'),
            'is_extended' => $this->isExtended,
            'extension_reason' => $this->extensionReason,
            'original_deadline' => $this->originalDeadline?->format('Y-m-d H:i:s'),
            'regulation' => $this->getRegulation(),
            'standard_days' => self::STANDARD_DEADLINE_DAYS,
            'max_extension_days' => self::MAX_EXTENSION_DAYS,
        ];
    }
}
