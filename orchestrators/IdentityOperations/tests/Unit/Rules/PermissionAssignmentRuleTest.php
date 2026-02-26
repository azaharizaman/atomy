<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Rules\PermissionAssignmentRule;
use Nexus\IdentityOperations\Rules\PermissionValidatorInterface;

final class PermissionAssignmentRuleTest extends TestCase
{
    private $validator;
    private $rule;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(PermissionValidatorInterface::class);
        $this->rule = new PermissionAssignmentRule($this->validator);
    }

    public function testEvaluatePassed(): void
    {
        $this->validator->expects($this->once())
            ->method('permissionExists')
            ->with('view.reports')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('userHasPermission')
            ->with('user-123', 'view.reports', 'tenant-1')
            ->willReturn(false);

        $result = $this->rule->evaluate(['permission' => 'view.reports', 'user_id' => 'user-123', 'tenant_id' => 'tenant-1']);

        $this->assertTrue($result->passed);
    }

    public function testEvaluateFailedWhenPermissionDoesNotExist(): void
    {
        $this->validator->expects($this->once())
            ->method('permissionExists')
            ->willReturn(false);

        $result = $this->rule->evaluate(['permission' => 'invalid', 'user_id' => 'user-123']);

        $this->assertFalse($result->passed);
        $this->assertEquals("Permission 'invalid' does not exist", $result->errors[0]['message']);
    }
}
