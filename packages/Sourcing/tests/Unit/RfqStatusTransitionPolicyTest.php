<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException;
use Nexus\Sourcing\Services\RfqStatusTransitionPolicy;
use PHPUnit\Framework\TestCase;

final class RfqStatusTransitionPolicyTest extends TestCase
{
    public function test_policy_accepts_alpha_status_transitions(): void
    {
        $policy = new RfqStatusTransitionPolicy();

        $this->assertTrue($policy->canTransition('  DRAFT ', 'published'));
        $this->assertTrue($policy->canTransition('published', ' CLOSED '));
        $this->assertTrue($policy->canTransition('closed', 'awarded'));
        $this->assertTrue($policy->canTransition('draft', 'cancelled'));
        $this->assertSame(['published', 'cancelled'], $policy->allowedTransitions('draft'));
    }

    public function test_policy_rejects_invalid_transitions_with_domain_exception(): void
    {
        $policy = new RfqStatusTransitionPolicy();

        $this->expectException(InvalidRfqStatusTransitionException::class);

        $policy->assertTransitionAllowed('awarded', 'published');
    }
}
