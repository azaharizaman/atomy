<?php

declare(strict_types=1);

namespace Nexus\Assets\Services;

use Nexus\Assets\Contracts\SettingsReaderInterface;

final readonly class SettingsReader implements SettingsReaderInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings = [])
    {
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->getValue($key);
        if ($value === null) {
            return $default;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key);
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            return $parsed ?? $default;
        }

        return $default;
    }

    private function getValue(string $key): mixed
    {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        $parts = explode('.', $key);
        $value = $this->settings;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }
}
