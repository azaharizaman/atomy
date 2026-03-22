<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Tests\Unit\Domain;

use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class PolicyRequestTest extends TestCase
{
    public function test_evaluation_context_preserves_explicit_action(): void
    {
        $request = new PolicyRequest(
            new TenantId('Tenant_A'),
            new PolicyId('policy.demo'),
            new PolicyVersion('v1'),
            'approve',
            ['action' => 'subject_action'],
            ['action' => 'resource_action'],
            ['action' => 'context_action']
        );

        $context = $request->evaluationContext();
        self::assertSame('approve', $context['action']);
    }
}
