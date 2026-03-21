<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\Enums;

use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use PHPUnit\Framework\TestCase;

final class IdempotencyRecordStatusTest extends TestCase
{
    public function testAllExpectedCasesExist(): void
    {
        $cases = IdempotencyRecordStatus::cases();
        $values = array_map(static fn (IdempotencyRecordStatus $status): string => $status->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('expired', $values);
    }
}
