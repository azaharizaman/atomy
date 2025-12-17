<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\RequestStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestStatus::class)]
final class RequestStatusTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'pending', 'verifying_identity', 'in_progress', 'awaiting_info',
            'under_review', 'completed', 'rejected', 'partially_completed',
            'cancelled', 'expired',
        ];

        $actualCases = array_map(fn(RequestStatus $s) => $s->value, RequestStatus::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    public function testTerminalStatusesAreTerminal(): void
    {
        $terminalStatuses = [
            RequestStatus::COMPLETED,
            RequestStatus::REJECTED,
            RequestStatus::CANCELLED,
            RequestStatus::EXPIRED,
        ];

        foreach ($terminalStatuses as $status) {
            $this->assertTrue($status->isTerminal(), "{$status->value} should be terminal");
        }
    }

    public function testNonTerminalStatusesAreNotTerminal(): void
    {
        $nonTerminalStatuses = [
            RequestStatus::PENDING,
            RequestStatus::VERIFYING_IDENTITY,
            RequestStatus::IN_PROGRESS,
            RequestStatus::AWAITING_INFO,
            RequestStatus::UNDER_REVIEW,
        ];

        foreach ($nonTerminalStatuses as $status) {
            $this->assertFalse($status->isTerminal(), "{$status->value} should not be terminal");
        }
    }

    public function testActiveStatusesAreActive(): void
    {
        $activeStatuses = [
            RequestStatus::IN_PROGRESS,
            RequestStatus::UNDER_REVIEW,
        ];

        foreach ($activeStatuses as $status) {
            $this->assertTrue($status->isActive(), "{$status->value} should be active");
        }
    }

    #[DataProvider('allStatusesProvider')]
    public function testGetLabelReturnsString(RequestStatus $status): void
    {
        $label = $status->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allStatusesProvider(): array
    {
        return array_map(fn($s) => [$s], RequestStatus::cases());
    }

    public function testPendingCanTransitionToVerifyingIdentity(): void
    {
        $this->assertTrue(
            RequestStatus::PENDING->canTransitionTo(RequestStatus::VERIFYING_IDENTITY)
        );
    }

    public function testCompletedCannotTransitionToInProgress(): void
    {
        $this->assertFalse(
            RequestStatus::COMPLETED->canTransitionTo(RequestStatus::IN_PROGRESS)
        );
    }
}
