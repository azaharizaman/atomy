<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Test for UserProfile value object
 * 
 * TDD Cycle 2: RED phase
 */
final class UserProfileTest extends TestCase
{
    public function test_it_can_be_created_with_required_fields(): void
    {
        $profile = new UserProfile(
            ssoUserId: 'azure-123',
            email: 'john.doe@company.com'
        );

        $this->assertSame('azure-123', $profile->ssoUserId);
        $this->assertSame('john.doe@company.com', $profile->email);
        $this->assertNull($profile->firstName);
        $this->assertNull($profile->lastName);
        $this->assertNull($profile->displayName);
        $this->assertSame([], $profile->attributes);
    }

    public function test_it_can_be_created_with_all_fields(): void
    {
        $profile = new UserProfile(
            ssoUserId: 'google-456',
            email: 'jane.smith@company.com',
            firstName: 'Jane',
            lastName: 'Smith',
            displayName: 'Jane Smith',
            attributes: [
                'department' => 'Engineering',
                'employee_id' => 'EMP-001',
            ]
        );

        $this->assertSame('google-456', $profile->ssoUserId);
        $this->assertSame('jane.smith@company.com', $profile->email);
        $this->assertSame('Jane', $profile->firstName);
        $this->assertSame('Smith', $profile->lastName);
        $this->assertSame('Jane Smith', $profile->displayName);
        $this->assertSame([
            'department' => 'Engineering',
            'employee_id' => 'EMP-001',
        ], $profile->attributes);
    }

    public function test_it_can_get_specific_attribute(): void
    {
        $profile = new UserProfile(
            ssoUserId: 'user-789',
            email: 'test@example.com',
            attributes: [
                'role' => 'admin',
                'team' => 'DevOps',
            ]
        );

        $this->assertSame('admin', $profile->attributes['role']);
        $this->assertSame('DevOps', $profile->attributes['team']);
        $this->assertArrayNotHasKey('non_existent', $profile->attributes);
    }

    public function test_it_is_immutable(): void
    {
        $profile = new UserProfile(
            ssoUserId: 'user-001',
            email: 'test@example.com'
        );

        // Attempting to modify should fail (readonly properties)
        $this->expectException(\Error::class);
        $profile->email = 'modified@example.com';
    }
}
