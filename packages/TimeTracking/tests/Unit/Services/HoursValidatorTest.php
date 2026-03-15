<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Tests\Unit\Services;

use Nexus\TimeTracking\Exceptions\InvalidHoursException;
use Nexus\TimeTracking\Services\HoursValidator;
use PHPUnit\Framework\TestCase;

final class HoursValidatorTest extends TestCase
{
    private HoursValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HoursValidator();
    }

    public function test_accepts_zero_hours(): void
    {
        $this->validator->validateEntry(0.0);
        self::assertTrue(true);
    }

    public function test_accepts_24_hours(): void
    {
        $this->validator->validateEntry(24.0);
        self::assertTrue(true);
    }

    public function test_throws_on_negative(): void
    {
        $this->expectException(InvalidHoursException::class);
        $this->expectExceptionMessage('negative');
        $this->validator->validateEntry(-0.5);
    }

    public function test_throws_on_over_24(): void
    {
        $this->expectException(InvalidHoursException::class);
        $this->expectExceptionMessage('cannot exceed');
        $this->validator->validateEntry(24.1);
    }

    public function test_validate_daily_total_accepts_under_24(): void
    {
        $this->validator->validateDailyTotal(8.0, 8.0);
        self::assertTrue(true);
    }

    public function test_validate_daily_total_throws_when_exceeded(): void
    {
        $this->expectException(InvalidHoursException::class);
        $this->expectExceptionMessage('Total hours per day');
        $this->validator->validateDailyTotal(20.0, 5.0);
    }
}
