<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\DTOs\UserContext;
use Nexus\IdentityOperations\DTOs\ValidationResult;

final class DtoTest extends TestCase
{
    public function testUserContext(): void
    {
        $context = new UserContext(
            userId: 'user-1',
            email: 'test@example.com',
            firstName: 'John',
            lastName: 'Doe',
            tenantId: 'tenant-1',
            status: 'active',
            permissions: ['view'],
            roles: ['user']
        );

        $this->assertTrue($context->isAuthenticated());
        $this->assertTrue($context->hasPermission('view'));
        $this->assertFalse($context->hasPermission('edit'));
        $this->assertTrue($context->hasRole('user'));
        $this->assertEquals('John Doe', $context->getFullName());

        $anonymous = UserContext::anonymous();
        $this->assertFalse($anonymous->isAuthenticated());
        $this->assertEquals('', $anonymous->getFullName());
    }

    public function testValidationResult(): void
    {
        $passed = ValidationResult::passed();
        $this->assertTrue($passed->passed);
        $this->assertEmpty($passed->errors);

        $failed = ValidationResult::failed([['rule' => 'test', 'message' => 'error', 'severity' => 'error']]);
        $this->assertFalse($failed->passed);
        $this->assertCount(1, $failed->errors);

        $withWarning = $passed->withWarning('rule', 'warn');
        $this->assertTrue($withWarning->passed);
        $this->assertCount(1, $withWarning->errors);
        $this->assertEquals('warning', $withWarning->errors[0]['severity']);

        $withError = $passed->withError('rule', 'err');
        $this->assertFalse($withError->passed);
        $this->assertCount(1, $withError->errors);
    }
}
