<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\Sourcing\Exceptions\UnsupportedRfqBulkActionException;
use Nexus\Sourcing\ValueObjects\RfqBulkAction;
use Nexus\Sourcing\ValueObjects\RfqDuplicationOptions;
use Nexus\Sourcing\ValueObjects\RfqLifecycleAction;
use PHPUnit\Framework\TestCase;

final class RfqLifecycleActionTest extends TestCase
{
    public function test_lifecycle_action_normalizes_input_deterministically(): void
    {
        $action = RfqLifecycleAction::fromString('  Save Draft  ');

        $this->assertSame('save_draft', $action->value());
        $this->assertSame('save_draft', (string) $action);
    }

    public function test_lifecycle_action_rejects_empty_input_with_domain_exception(): void
    {
        $this->expectException(RfqLifecyclePreconditionException::class);

        RfqLifecycleAction::fromString('   ');
    }

    public function test_bulk_action_normalizes_allowed_actions(): void
    {
        $close = RfqBulkAction::fromString(' CLOSE ');
        $cancel = RfqBulkAction::fromString('cancel');

        $this->assertSame('close', $close->value());
        $this->assertSame('cancel', $cancel->value());
    }

    public function test_bulk_action_rejects_unsupported_actions(): void
    {
        $this->expectException(UnsupportedRfqBulkActionException::class);

        RfqBulkAction::fromString('archive');
    }

    public function test_duplication_options_default_to_copying_only_line_items(): void
    {
        $options = new RfqDuplicationOptions();

        $this->assertTrue($options->copyLineItems);
        $this->assertFalse($options->copyVendorInvitations);
        $this->assertFalse($options->copyQuotes);
        $this->assertFalse($options->copyAwards);
    }
}
