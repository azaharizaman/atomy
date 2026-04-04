<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException;
use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
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
        $this->assertTrue($policy->canTransition('published', 'cancelled'));
        $this->assertTrue($policy->canTransition('closed', 'cancelled'));
        $this->assertSame(['published', 'cancelled'], $policy->allowedTransitions('draft'));
        $this->assertSame(['closed', 'cancelled'], $policy->allowedTransitions('published'));
        $this->assertSame(['awarded', 'cancelled'], $policy->allowedTransitions('closed'));
    }

    public function test_policy_rejects_invalid_transitions_with_domain_exception(): void
    {
        $policy = new RfqStatusTransitionPolicy();

        $this->expectException(InvalidRfqStatusTransitionException::class);

        $policy->assertTransitionAllowed('awarded', 'published');
    }

    public function test_policy_rejects_unknown_status_vocabulary(): void
    {
        $policy = new RfqStatusTransitionPolicy();

        $this->expectException(RfqLifecyclePreconditionException::class);

        $policy->allowedTransitions('archived');
    }
}
