<?php

declare(strict_types=1);

namespace Nexus\PDPA\Tests\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\PDPA\Exceptions\PdpaException;
use Nexus\PDPA\ValueObjects\PdpaDeadline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpaDeadline::class)]
final class PdpaDeadlineTest extends TestCase
{
    #[Test]
    public function it_creates_deadline_from_data_subject_request(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // PDPA: 21 days from submission
        $expectedDeadline = $submittedAt->modify('+21 days');
        $this->assertEquals($expectedDeadline, $deadline->getDeadlineDate());
        $this->assertEquals($submittedAt, $deadline->getStartDate());
        $this->assertFalse($deadline->isExtended());
    }

    #[Test]
    public function it_uses_21_day_standard_deadline(): void
    {
        $this->assertEquals(21, PdpaDeadline::STANDARD_DEADLINE_DAYS);
    }

    #[Test]
    public function it_uses_14_day_maximum_extension(): void
    {
        $this->assertEquals(14, PdpaDeadline::MAX_EXTENSION_DAYS);
    }

    #[Test]
    public function it_calculates_days_remaining_correctly(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // Check at submission date - 21 days remaining
        $this->assertEquals(21, $deadline->getDaysRemaining($submittedAt));

        // Check 10 days later - 11 days remaining
        $checkDate = $submittedAt->modify('+10 days');
        $this->assertEquals(11, $deadline->getDaysRemaining($checkDate));

        // Check on deadline day - 0 days remaining
        $deadlineDate = $submittedAt->modify('+21 days');
        $this->assertEquals(0, $deadline->getDaysRemaining($deadlineDate));
    }

    #[Test]
    public function it_returns_negative_days_when_overdue(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // Check 25 days later - 4 days overdue
        $checkDate = $submittedAt->modify('+25 days');
        $this->assertEquals(-4, $deadline->getDaysRemaining($checkDate));
    }

    #[Test]
    public function it_detects_overdue_status(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // Not overdue on deadline day
        $deadlineDate = $submittedAt->modify('+21 days');
        $this->assertFalse($deadline->isOverdue($deadlineDate));

        // Overdue after deadline
        $overdueDate = $submittedAt->modify('+22 days');
        $this->assertTrue($deadline->isOverdue($overdueDate));
    }

    #[Test]
    public function it_calculates_days_overdue(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // Not overdue - returns 0
        $this->assertEquals(0, $deadline->getDaysOverdue($submittedAt->modify('+10 days')));

        // On deadline - returns 0
        $this->assertEquals(0, $deadline->getDaysOverdue($submittedAt->modify('+21 days')));

        // 5 days overdue
        $this->assertEquals(5, $deadline->getDaysOverdue($submittedAt->modify('+26 days')));
    }

    #[Test]
    public function it_extends_deadline_with_reason(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $originalDeadline = PdpaDeadline::forDataSubjectRequest($request);

        $extendedDeadline = $originalDeadline->extend('Commissioner approved extension');

        $this->assertTrue($extendedDeadline->isExtended());
        $this->assertEquals('Commissioner approved extension', $extendedDeadline->getExtensionReason());

        // Extended by 14 days (PDPA max)
        $expectedExtendedDate = $submittedAt->modify('+35 days'); // 21 + 14
        $this->assertEquals($expectedExtendedDate, $extendedDeadline->getDeadlineDate());
    }

    #[Test]
    public function it_allows_custom_extension_days(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $extendedDeadline = $deadline->extend('Complex request', 7);

        // 21 + 7 = 28 days
        $expectedDate = $submittedAt->modify('+28 days');
        $this->assertEquals($expectedDate, $extendedDeadline->getDeadlineDate());
    }

    #[Test]
    public function it_throws_exception_when_extending_already_extended_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $extendedDeadline = $deadline->extend('First extension');

        $this->expectException(PdpaException::class);
        $extendedDeadline->extend('Second extension');
    }

    #[Test]
    public function it_throws_exception_when_extension_exceeds_maximum(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $this->expectException(PdpaException::class);
        $deadline->extend('Over limit extension', 30); // Exceeds 14-day max
    }

    #[Test]
    public function it_provides_regulation_info(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $this->assertEquals('PDPA 2010', $deadline->getRegulation());
        $this->assertEquals(21, $deadline->getStandardDeadlineDays());
        $this->assertEquals(14, $deadline->getMaxExtensionDays());
    }

    #[Test]
    public function it_converts_to_array_correctly(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $array = $deadline->toArray();

        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('deadline_date', $array);
        $this->assertArrayHasKey('is_extended', $array);
        $this->assertArrayHasKey('extension_reason', $array);
        $this->assertArrayHasKey('regulation', $array);
        $this->assertArrayHasKey('standard_days', $array);
        $this->assertArrayHasKey('max_extension_days', $array);

        $this->assertFalse($array['is_extended']);
        $this->assertNull($array['extension_reason']);
        $this->assertEquals('PDPA 2010', $array['regulation']);
        $this->assertEquals(21, $array['standard_days']);
        $this->assertEquals(14, $array['max_extension_days']);
    }

    #[Test]
    public function it_includes_extension_info_in_array_when_extended(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        $extendedDeadline = $deadline->extend('Complex verification required');
        $array = $extendedDeadline->toArray();

        $this->assertTrue($array['is_extended']);
        $this->assertEquals('Complex verification required', $array['extension_reason']);
    }

    #[Test]
    #[DataProvider('requestTypeProvider')]
    public function it_applies_same_deadline_for_all_request_types(RequestType $type): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, $type);
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // PDPA: Same 21-day deadline for all request types
        $expectedDeadline = $submittedAt->modify('+21 days');
        $this->assertEquals($expectedDeadline, $deadline->getDeadlineDate());
    }

    public static function requestTypeProvider(): array
    {
        return [
            'access' => [RequestType::ACCESS],
            'rectification' => [RequestType::RECTIFICATION],
            'erasure' => [RequestType::ERASURE],
            'portability' => [RequestType::PORTABILITY],
            'objection' => [RequestType::OBJECTION],
        ];
    }

    #[Test]
    public function it_creates_from_specific_date(): void
    {
        $startDate = new DateTimeImmutable('2024-03-01');
        $deadline = PdpaDeadline::fromDate($startDate);

        $expectedDeadline = $startDate->modify('+21 days');
        $this->assertEquals($expectedDeadline, $deadline->getDeadlineDate());
        $this->assertEquals($startDate, $deadline->getStartDate());
    }

    private function createRequest(
        DateTimeImmutable $submittedAt,
        RequestType $type = RequestType::ACCESS
    ): DataSubjectRequest {
        $dataSubjectId = new DataSubjectId('party:ds-001');
        $deadline = $submittedAt->modify('+21 days');
        
        return new DataSubjectRequest(
            id: 'req-test-001',
            dataSubjectId: $dataSubjectId,
            type: $type,
            status: RequestStatus::PENDING,
            submittedAt: $submittedAt,
            deadline: $deadline,
            metadata: []
        );
    }
}
