<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Enums;

use Nexus\Messaging\Enums\ArchivalStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\Enums\ArchivalStatus
 */
final class ArchivalStatusTest extends TestCase
{
    public function testActiveStatus(): void
    {
        $status = ArchivalStatus::Active;
        
        $this->assertTrue($status->isActive());
        $this->assertFalse($status->isArchived());
        $this->assertFalse($status->isPendingArchival());
        $this->assertSame('Active', $status->label());
        $this->assertSame('active', $status->value);
    }

    public function testPreArchivedStatus(): void
    {
        $status = ArchivalStatus::PreArchived;
        
        $this->assertFalse($status->isActive());
        $this->assertFalse($status->isArchived());
        $this->assertTrue($status->isPendingArchival());
        $this->assertSame('Pending Archival', $status->label());
        $this->assertSame('pre_archived', $status->value);
    }

    public function testArchivedStatus(): void
    {
        $status = ArchivalStatus::Archived;
        
        $this->assertFalse($status->isActive());
        $this->assertTrue($status->isArchived());
        $this->assertFalse($status->isPendingArchival());
        $this->assertSame('Archived', $status->label());
        $this->assertSame('archived', $status->value);
    }

    public function testAllStatusesExist(): void
    {
        $this->assertCount(3, ArchivalStatus::cases());
        $this->assertContains(ArchivalStatus::Active, ArchivalStatus::cases());
        $this->assertContains(ArchivalStatus::PreArchived, ArchivalStatus::cases());
        $this->assertContains(ArchivalStatus::Archived, ArchivalStatus::cases());
    }
}
