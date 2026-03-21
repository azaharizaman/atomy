<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\Enums;

use Nexus\Idempotency\Enums\BeginOutcome;
use PHPUnit\Framework\TestCase;

final class BeginOutcomeTest extends TestCase
{
    public function testAllExpectedCasesExist(): void
    {
        $values = array_map(static fn (BeginOutcome $o): string => $o->value, BeginOutcome::cases());

        $this->assertContains('first_execution', $values);
        $this->assertContains('replay', $values);
        $this->assertContains('in_progress', $values);
    }
}
