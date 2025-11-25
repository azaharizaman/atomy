<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for Manufacturing package tests.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Create a mock date.
     */
    protected function createDate(string $date = '2024-01-15'): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    /**
     * Create a future date.
     */
    protected function createFutureDate(int $daysFromNow = 30): \DateTimeImmutable
    {
        return new \DateTimeImmutable("+{$daysFromNow} days");
    }

    /**
     * Create a past date.
     */
    protected function createPastDate(int $daysAgo = 30): \DateTimeImmutable
    {
        return new \DateTimeImmutable("-{$daysAgo} days");
    }

    /**
     * Generate a unique ID.
     */
    protected function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Assert that array has expected keys.
     *
     * @param array<string> $expectedKeys
     * @param array<string, mixed> $array
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Array missing expected key: {$key}");
        }
    }
}
