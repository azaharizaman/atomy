<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Rules\UserTenantAccessRule;
use Nexus\IdentityOperations\Rules\TenantAccessCheckerInterface;

final class UserTenantAccessRuleTest extends TestCase
{
    private $checker;
    private $rule;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(TenantAccessCheckerInterface::class);
        $this->rule = new UserTenantAccessRule($this->checker);
    }

    public function testEvaluatePassed(): void
    {
        $this->checker->expects($this->once())
            ->method('hasAccess')
            ->with('user-123', 'tenant-1')
            ->willReturn(true);

        $result = $this->rule->evaluate(['user_id' => 'user-123', 'tenant_id' => 'tenant-1']);

        $this->assertTrue($result->passed);
    }

    public function testEvaluateFailed(): void
    {
        $this->checker->expects($this->once())
            ->method('hasAccess')
            ->willReturn(false);

        $result = $this->rule->evaluate(['user_id' => 'user-123', 'tenant_id' => 'tenant-1']);

        $this->assertFalse($result->passed);
        $this->assertEquals("User 'user-123' does not have access to tenant 'tenant-1'", $result->errors[0]['message']);
    }
}
