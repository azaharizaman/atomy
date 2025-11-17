<?php

declare(strict_types=1);

namespace Nexus\Tenant\ValueObjects;

/**
 * Tenant Settings Value Object
 *
 * Immutable value object representing tenant-specific configuration settings.
 *
 * @package Nexus\Tenant\ValueObjects
 */
final class TenantSettings
{
    /**
     * @param string $timezone
     * @param string $locale
     * @param string $currency
     * @param string $dateFormat
     * @param string $timeFormat
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $timezone = 'UTC',
        private readonly string $locale = 'en',
        private readonly string $currency = 'USD',
        private readonly string $dateFormat = 'Y-m-d',
        private readonly string $timeFormat = 'H:i:s',
        private readonly array $metadata = []
    ) {
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function withTimezone(string $timezone): self
    {
        return new self(
            $timezone,
            $this->locale,
            $this->currency,
            $this->dateFormat,
            $this->timeFormat,
            $this->metadata
        );
    }

    public function withLocale(string $locale): self
    {
        return new self(
            $this->timezone,
            $locale,
            $this->currency,
            $this->dateFormat,
            $this->timeFormat,
            $this->metadata
        );
    }

    public function withCurrency(string $currency): self
    {
        return new self(
            $this->timezone,
            $this->locale,
            $currency,
            $this->dateFormat,
            $this->timeFormat,
            $this->metadata
        );
    }

    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->timezone,
            $this->locale,
            $this->currency,
            $this->dateFormat,
            $this->timeFormat,
            $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'date_format' => $this->dateFormat,
            'time_format' => $this->timeFormat,
            'metadata' => $this->metadata,
        ];
    }
}
