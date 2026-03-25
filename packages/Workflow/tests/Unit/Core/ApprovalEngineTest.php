<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Core;

use Nexus\Workflow\Contracts\ApprovalStrategyInterface;
use Nexus\Workflow\Core\ApprovalEngine;
use Nexus\Workflow\Exceptions\UnknownApprovalStrategyException;
use PHPUnit\Framework\TestCase;

final class ApprovalEngineTest extends TestCase
{
    public function testCanProceedThrowsUnknownApprovalStrategyExceptionForUnregisteredStrategy(): void
    {
        $engine = new ApprovalEngine();

        $this->expectException(UnknownApprovalStrategyException::class);
        $this->expectExceptionMessage("Approval strategy 'missing' not found.");

        $engine->canProceed('missing', []);
    }

    public function testShouldRejectThrowsUnknownApprovalStrategyExceptionForUnregisteredStrategy(): void
    {
        $engine = new ApprovalEngine();

        $this->expectException(UnknownApprovalStrategyException::class);
        $this->expectExceptionMessage("Approval strategy 'missing' not found.");

        $engine->shouldReject('missing', []);
    }

    public function testRegisteredStrategyIsUsedForCanProceedAndShouldReject(): void
    {
        $strategy = new class implements ApprovalStrategyInterface {
            public function getName(): string
            {
                return 'stub';
            }

            public function canProceed(array $approvals, array $config = []): bool
            {
                return ($config['wantProceed'] ?? false) === true;
            }

            public function shouldReject(array $approvals, array $config = []): bool
            {
                return ($config['wantReject'] ?? false) === true;
            }
        };

        $engine = new ApprovalEngine();
        $engine->registerStrategy($strategy);

        self::assertTrue($engine->canProceed('stub', [], ['wantProceed' => true]));
        self::assertFalse($engine->canProceed('stub', [], ['wantProceed' => false]));

        self::assertTrue($engine->shouldReject('stub', [], ['wantReject' => true]));
        self::assertFalse($engine->shouldReject('stub', [], ['wantReject' => false]));
    }
}
