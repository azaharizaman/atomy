<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Rules\UserStatusRule;
use Nexus\IdentityOperations\Rules\UserStatusCheckerInterface;

final class UserStatusRuleTest extends TestCase
{
    private $checker;
    private $rule;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(UserStatusCheckerInterface::class);
        $this->rule = new UserStatusRule($this->checker);
    }

    public function testEvaluatePassed(): void
    {
        $this->checker->expects($this->once())
            ->method('getStatus')
            ->with('user-123')
            ->willReturn('active');

        $result = $this->rule->evaluate('user-123');

        $this->assertTrue($result->passed);
    }

    public function testEvaluateFailedForSuspended(): void
    {
        $this->checker->expects($this->once())
            ->method('getStatus')
            ->with('user-123')
            ->willReturn('suspended');

        $result = $this->rule->evaluate('user-123');

        $this->assertFalse($result->passed);
        $this->assertEquals("User status 'suspended' does not allow this operation", $result->errors[0]['message']);
    }

    public function testEvaluateFailedForNotFound(): void
    {
        $this->checker->expects($this->once())
            ->method('getStatus')
            ->with('user-123')
            ->willReturn(null);

        $result = $this->rule->evaluate('user-123');

        $this->assertFalse($result->passed);
        $this->assertEquals("User 'user-123' not found", $result->errors[0]['message']);
    }
}
