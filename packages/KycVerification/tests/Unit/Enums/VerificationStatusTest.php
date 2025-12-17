<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\VerificationStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VerificationStatus::class)]
final class VerificationStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = [
            'PENDING', 'IN_PROGRESS', 'DOCUMENTS_REQUIRED', 'UNDER_REVIEW',
            'VERIFIED', 'CONDITIONALLY_VERIFIED', 'REJECTED', 'EXPIRED', 'SUSPENDED',
        ];

        $actualCases = array_map(fn(VerificationStatus $case) => $case->name, VerificationStatus::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    #[DataProvider('activeStatusProvider')]
    public function it_identifies_active_statuses(VerificationStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isActive());
    }

    public static function activeStatusProvider(): array
    {
        return [
            'verified is active' => [VerificationStatus::VERIFIED, true],
            'conditionally verified is active' => [VerificationStatus::CONDITIONALLY_VERIFIED, true],
            'pending is not active' => [VerificationStatus::PENDING, false],
            'rejected is not active' => [VerificationStatus::REJECTED, false],
            'expired is not active' => [VerificationStatus::EXPIRED, false],
        ];
    }

    #[Test]
    #[DataProvider('transactionStatusProvider')]
    public function it_identifies_statuses_allowing_transactions(VerificationStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->allowsTransactions());
    }

    public static function transactionStatusProvider(): array
    {
        return [
            'verified allows transactions' => [VerificationStatus::VERIFIED, true],
            'conditionally verified allows transactions' => [VerificationStatus::CONDITIONALLY_VERIFIED, true],
            'pending does not allow' => [VerificationStatus::PENDING, false],
            'rejected does not allow' => [VerificationStatus::REJECTED, false],
        ];
    }

    #[Test]
    #[DataProvider('actionRequiredProvider')]
    public function it_identifies_statuses_requiring_action(VerificationStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->requiresAction());
    }

    public static function actionRequiredProvider(): array
    {
        return [
            'pending requires action' => [VerificationStatus::PENDING, true],
            'documents required requires action' => [VerificationStatus::DOCUMENTS_REQUIRED, true],
            'under review requires action' => [VerificationStatus::UNDER_REVIEW, true],
            'expired requires action' => [VerificationStatus::EXPIRED, true],
            'verified does not require action' => [VerificationStatus::VERIFIED, false],
        ];
    }

    #[Test]
    #[DataProvider('terminalStatusProvider')]
    public function it_identifies_terminal_statuses(VerificationStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isTerminal());
    }

    public static function terminalStatusProvider(): array
    {
        return [
            'rejected is terminal' => [VerificationStatus::REJECTED, true],
            'expired is terminal' => [VerificationStatus::EXPIRED, true],
            'pending is not terminal' => [VerificationStatus::PENDING, false],
            'verified is not terminal' => [VerificationStatus::VERIFIED, false],
        ];
    }

    #[Test]
    public function it_returns_human_readable_label(): void
    {
        $this->assertSame('Pending', VerificationStatus::PENDING->label());
        $this->assertSame('In Progress', VerificationStatus::IN_PROGRESS->label());
        $this->assertSame('Documents Required', VerificationStatus::DOCUMENTS_REQUIRED->label());
        $this->assertSame('Verified', VerificationStatus::VERIFIED->label());
        $this->assertSame('Rejected', VerificationStatus::REJECTED->label());
    }

    #[Test]
    public function it_returns_allowed_transitions(): void
    {
        $pendingTransitions = VerificationStatus::PENDING->allowedTransitions();
        $this->assertContains(VerificationStatus::IN_PROGRESS, $pendingTransitions);
        $this->assertContains(VerificationStatus::DOCUMENTS_REQUIRED, $pendingTransitions);
        $this->assertContains(VerificationStatus::REJECTED, $pendingTransitions);

        // Rejected is terminal - no transitions allowed
        $rejectedTransitions = VerificationStatus::REJECTED->allowedTransitions();
        $this->assertEmpty($rejectedTransitions);
    }

    #[Test]
    public function it_can_check_if_transition_is_allowed(): void
    {
        $this->assertTrue(VerificationStatus::PENDING->canTransitionTo(VerificationStatus::IN_PROGRESS));
        $this->assertFalse(VerificationStatus::PENDING->canTransitionTo(VerificationStatus::VERIFIED));
        $this->assertFalse(VerificationStatus::REJECTED->canTransitionTo(VerificationStatus::VERIFIED));
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('pending', VerificationStatus::PENDING->value);
        $this->assertSame('verified', VerificationStatus::VERIFIED->value);
        $this->assertSame('rejected', VerificationStatus::REJECTED->value);
    }
}
