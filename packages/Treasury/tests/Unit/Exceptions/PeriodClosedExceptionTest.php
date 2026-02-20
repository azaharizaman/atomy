<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use DateTimeImmutable;
use Nexus\Treasury\Exceptions\PeriodClosedException;
use PHPUnit\Framework\TestCase;

final class PeriodClosedExceptionTest extends TestCase
{
    public function test_for_date_creates_exception(): void
    {
        $date = new DateTimeImmutable('2024-01-15');
        $exception = PeriodClosedException::forDate($date);

        $this->assertStringContainsString('2024-01-15', $exception->getMessage());
    }

    public function test_for_period_id_creates_exception(): void
    {
        $exception = PeriodClosedException::forPeriodId('PER-2024-01');

        $this->assertStringContainsString('PER-2024-01', $exception->getMessage());
    }

    public function test_cannot_post_to_closed_period_creates_exception(): void
    {
        $exception = PeriodClosedException::cannotPostToClosedPeriod('PER-2024-01', 'payment');

        $this->assertStringContainsString('PER-2024-01', $exception->getMessage());
        $this->assertStringContainsString('payment', $exception->getMessage());
    }

    public function test_fiscal_year_closed_creates_exception(): void
    {
        $exception = PeriodClosedException::fiscalYearClosed('2024');

        $this->assertStringContainsString('2024', $exception->getMessage());
    }
}
