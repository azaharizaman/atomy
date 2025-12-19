<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use Nexus\Payment\Enums\SettlementBatchStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettlementBatchStatus::class)]
final class SettlementBatchStatusTest extends TestCase
{
    #[Test]
    public function it_has_correct_cases(): void
    {
        $cases = SettlementBatchStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(SettlementBatchStatus::OPEN, $cases);
        $this->assertContains(SettlementBatchStatus::CLOSED, $cases);
        $this->assertContains(SettlementBatchStatus::RECONCILED, $cases);
        $this->assertContains(SettlementBatchStatus::DISPUTED, $cases);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(SettlementBatchStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'open' => [SettlementBatchStatus::OPEN, 'Open'],
            'closed' => [SettlementBatchStatus::CLOSED, 'Closed'],
            'reconciled' => [SettlementBatchStatus::RECONCILED, 'Reconciled'],
            'disputed' => [SettlementBatchStatus::DISPUTED, 'Disputed'],
        ];
    }

    #[Test]
    #[DataProvider('terminalStatusProvider')]
    public function it_correctly_identifies_terminal_status(SettlementBatchStatus $status, bool $expectedTerminal): void
    {
        $this->assertSame($expectedTerminal, $status->isTerminal());
    }

    public static function terminalStatusProvider(): array
    {
        return [
            'open is not terminal' => [SettlementBatchStatus::OPEN, false],
            'closed is not terminal' => [SettlementBatchStatus::CLOSED, false],
            'reconciled is terminal' => [SettlementBatchStatus::RECONCILED, true],
            'disputed is not terminal' => [SettlementBatchStatus::DISPUTED, false],
        ];
    }

    #[Test]
    public function it_identifies_open_status(): void
    {
        $this->assertTrue(SettlementBatchStatus::OPEN->isOpen());
        $this->assertFalse(SettlementBatchStatus::CLOSED->isOpen());
        $this->assertFalse(SettlementBatchStatus::RECONCILED->isOpen());
        $this->assertFalse(SettlementBatchStatus::DISPUTED->isOpen());
    }

    #[Test]
    public function it_identifies_can_reconcile(): void
    {
        $this->assertFalse(SettlementBatchStatus::OPEN->canReconcile());
        $this->assertTrue(SettlementBatchStatus::CLOSED->canReconcile());
        $this->assertFalse(SettlementBatchStatus::RECONCILED->canReconcile());
        $this->assertTrue(SettlementBatchStatus::DISPUTED->canReconcile());
    }

    #[Test]
    #[DataProvider('validTransitionsProvider')]
    public function it_returns_valid_transitions(SettlementBatchStatus $status, array $expectedTransitions): void
    {
        $this->assertSame($expectedTransitions, $status->getValidTransitions());
    }

    public static function validTransitionsProvider(): array
    {
        return [
            'open can close' => [
                SettlementBatchStatus::OPEN,
                [SettlementBatchStatus::CLOSED],
            ],
            'closed can reconcile or dispute' => [
                SettlementBatchStatus::CLOSED,
                [SettlementBatchStatus::RECONCILED, SettlementBatchStatus::DISPUTED],
            ],
            'reconciled is terminal' => [
                SettlementBatchStatus::RECONCILED,
                [],
            ],
            'disputed can reconcile' => [
                SettlementBatchStatus::DISPUTED,
                [SettlementBatchStatus::RECONCILED],
            ],
        ];
    }

    #[Test]
    public function it_validates_transitions_correctly(): void
    {
        // OPEN -> CLOSED is valid
        $this->assertTrue(SettlementBatchStatus::OPEN->canTransitionTo(SettlementBatchStatus::CLOSED));
        
        // OPEN -> RECONCILED is invalid
        $this->assertFalse(SettlementBatchStatus::OPEN->canTransitionTo(SettlementBatchStatus::RECONCILED));
        
        // CLOSED -> RECONCILED is valid
        $this->assertTrue(SettlementBatchStatus::CLOSED->canTransitionTo(SettlementBatchStatus::RECONCILED));
        
        // CLOSED -> DISPUTED is valid
        $this->assertTrue(SettlementBatchStatus::CLOSED->canTransitionTo(SettlementBatchStatus::DISPUTED));
        
        // DISPUTED -> RECONCILED is valid
        $this->assertTrue(SettlementBatchStatus::DISPUTED->canTransitionTo(SettlementBatchStatus::RECONCILED));
        
        // RECONCILED cannot transition to anything
        $this->assertFalse(SettlementBatchStatus::RECONCILED->canTransitionTo(SettlementBatchStatus::OPEN));
        $this->assertFalse(SettlementBatchStatus::RECONCILED->canTransitionTo(SettlementBatchStatus::CLOSED));
    }
}
