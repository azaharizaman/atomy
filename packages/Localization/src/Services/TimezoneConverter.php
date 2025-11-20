<?php

declare(strict_types=1);

namespace Nexus\Localization\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Nexus\Localization\ValueObjects\Timezone;

/**
 * Timezone converter service.
 *
 * Converts between UTC and user timezones.
 */
final class TimezoneConverter
{
    /**
     * Convert UTC datetime to user timezone.
     */
    public function toUserTimezone(
        DateTimeInterface $utcDatetime,
        Timezone $userTimezone
    ): DateTimeImmutable {
        if ($utcDatetime instanceof DateTimeImmutable) {
            return $utcDatetime->setTimezone($userTimezone->toDateTimeZone());
        }

        return DateTimeImmutable::createFromInterface($utcDatetime)
            ->setTimezone($userTimezone->toDateTimeZone());
    }

    /**
     * Convert user timezone datetime to UTC.
     */
    public function toUtc(
        DateTimeInterface $userDatetime,
        Timezone $userTimezone
    ): DateTimeImmutable {
        if ($userDatetime instanceof DateTimeImmutable) {
            $inUserTz = $userDatetime->setTimezone($userTimezone->toDateTimeZone());
            return $inUserTz->setTimezone(new \DateTimeZone('UTC'));
        }

        return DateTimeImmutable::createFromInterface($userDatetime)
            ->setTimezone($userTimezone->toDateTimeZone())
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Get current datetime in user timezone.
     */
    public function now(Timezone $userTimezone): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $userTimezone->toDateTimeZone());
    }

    /**
     * Get current UTC datetime.
     */
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /**
     * Convert between two timezones.
     */
    public function convert(
        DateTimeInterface $datetime,
        Timezone $fromTimezone,
        Timezone $toTimezone
    ): DateTimeImmutable {
        if ($datetime instanceof DateTimeImmutable) {
            $inSourceTz = $datetime->setTimezone($fromTimezone->toDateTimeZone());
            return $inSourceTz->setTimezone($toTimezone->toDateTimeZone());
        }

        return DateTimeImmutable::createFromInterface($datetime)
            ->setTimezone($fromTimezone->toDateTimeZone())
            ->setTimezone($toTimezone->toDateTimeZone());
    }
}
