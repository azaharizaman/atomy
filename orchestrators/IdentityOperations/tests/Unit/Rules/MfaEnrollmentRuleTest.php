<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Rules\MfaEnrollmentRule;
use Nexus\IdentityOperations\Rules\MfaEnrollmentCheckerInterface;

final class MfaEnrollmentRuleTest extends TestCase
{
    private readonly MfaEnrollmentCheckerInterface|MockObject $checker;
    private readonly MfaEnrollmentRule $rule;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(MfaEnrollmentCheckerInterface::class);
        $this->rule = new MfaEnrollmentRule($this->checker);
    }

    public function testEvaluatePassedWhenNotRequired(): void
    {
        $this->checker->expects($this->once())
            ->method('isEnrolled')
            ->with('user-123', null)
            ->willReturn(false);

        $result = $this->rule->evaluate(['user_id' => 'user-123', 'require_mfa' => false]);

        $this->assertTrue($result->passed);
    }

    public function testEvaluateFailedWhenRequiredAndNotEnrolled(): void
    {
        $this->checker->expects($this->once())
            ->method('isEnrolled')
            ->with('user-123', 'tenant-1')
            ->willReturn(false);

        $result = $this->rule->evaluate(['user_id' => 'user-123', 'require_mfa' => true, 'tenant_id' => 'tenant-1']);

        $this->assertFalse($result->passed);
        $this->assertEquals("MFA is required but user 'user-123' is not enrolled", $result->errors[0]['message']);
    }
}
