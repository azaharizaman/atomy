<?php

declare(strict_types=1);

namespace Nexus\Document\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for Document package tests.
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
     * Create a past date.
     */
    protected function createPastDate(int $daysAgo = 30): \DateTimeImmutable
    {
        return new \DateTimeImmutable("-{$daysAgo} days");
    }

    /**
     * Create a future date.
     */
    protected function createFutureDate(int $daysFromNow = 30): \DateTimeImmutable
    {
        return new \DateTimeImmutable("+{$daysFromNow} days");
    }

    /**
     * Create a test ULID.
     */
    protected function createTestId(string $suffix = '001'): string
    {
        return "01HXYZ{$suffix}ABCDEFG12345678";
    }

    /**
     * Create a test tenant ID.
     */
    protected function createTenantId(): string
    {
        return 'tenant-test-001';
    }

    /**
     * Create a test user ID.
     */
    protected function createUserId(string $role = 'admin'): string
    {
        return "user-{$role}-001";
    }
}
