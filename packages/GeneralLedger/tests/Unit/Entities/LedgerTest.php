<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Exceptions\LedgerAlreadyClosedException;
use Nexus\GeneralLedger\Exceptions\LedgerArchivedException;
use Nexus\GeneralLedger\Exceptions\LedgerAlreadyActiveException;

final class LedgerTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $ledger = Ledger::create(
            'id',
            'tenant',
            'Name',
            'USD',
            LedgerType::STATUTORY,
            'Description'
        );

        $this->assertEquals('id', $ledger->id);
        $this->assertEquals('Name', $ledger->name);
        $this->assertEquals('USD', $ledger->currency);
        $this->assertEquals(LedgerType::STATUTORY, $ledger->type);
        $this->assertEquals(LedgerStatus::ACTIVE, $ledger->status);
        $this->assertTrue($ledger->isActive());
        $this->assertFalse($ledger->isClosed());
        $this->assertTrue($ledger->canPostTransactions());
    }

    public function test_it_can_be_closed(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        $closed = $ledger->close();

        $this->assertEquals(LedgerStatus::CLOSED, $closed->status);
        $this->assertTrue($closed->isClosed());
        $this->assertFalse($closed->isActive());
        $this->assertFalse($closed->canPostTransactions());
        $this->assertNotNull($closed->closedAt);
    }

    public function test_it_cannot_be_closed_twice(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        $closed = $ledger->close();

        $this->expectException(LedgerAlreadyClosedException::class);
        $closed->close();
    }

    public function test_it_can_be_archived(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        $archived = $ledger->archive();

        $this->assertEquals(LedgerStatus::ARCHIVED, $archived->status);
        $this->assertTrue($archived->isArchived());
    }

    public function test_it_can_be_reactivated(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        $closed = $ledger->close();
        $reactivated = $closed->reactivate();

        $this->assertEquals(LedgerStatus::ACTIVE, $reactivated->status);
        $this->assertTrue($reactivated->isActive());
        $this->assertNull($reactivated->closedAt);
    }

    public function test_it_cannot_be_archived_twice(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        $archived = $ledger->archive();

        $this->expectException(LedgerArchivedException::class);
        $archived->archive();
    }

    public function test_it_cannot_be_reactivated_if_already_active(): void
    {
        $ledger = Ledger::create('id', 'tenant', 'Name', 'USD', LedgerType::STATUTORY);
        
        $this->expectException(LedgerAlreadyActiveException::class);
        $ledger->reactivate();
    }

    public function test_it_throws_exception_on_invalid_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Ledger(
            'id',
            'tenant',
            'Name',
            'INVALID',
            LedgerType::STATUTORY,
            LedgerStatus::ACTIVE,
            new \DateTimeImmutable()
        );
    }
}
