<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Services;

use Nexus\SSO\Contracts\AttributeMapperInterface;
use Nexus\SSO\Exceptions\AttributeMappingException;
use Nexus\SSO\Services\AttributeMapper;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\UserProfile;
use PHPUnit\Framework\TestCase;

final class AttributeMapperTest extends TestCase
{
    private AttributeMapperInterface $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AttributeMapper();
    }

    public function test_it_maps_attributes_correctly(): void
    {
        $ssoAttributes = [
            'sub' => 'user-123',
            'email' => 'john.doe@example.com',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'name' => 'John Doe',
        ];

        $attributeMap = new AttributeMap(
            mappings: [
                'sso_user_id' => 'sub',
                'email' => 'email',
                'first_name' => 'given_name',
                'last_name' => 'family_name',
                'display_name' => 'name',
            ],
            requiredFields: ['email', 'sso_user_id']
        );

        $profile = $this->mapper->map($ssoAttributes, $attributeMap);

        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertSame('user-123', $profile->ssoUserId);
        $this->assertSame('john.doe@example.com', $profile->email);
        $this->assertSame('John', $profile->firstName);
        $this->assertSame('Doe', $profile->lastName);
        $this->assertSame('John Doe', $profile->displayName);
    }

    public function test_it_handles_empty_mappings(): void
    {
        $ssoAttributes = [
            'sso_user_id' => 'user-456',
            'email' => 'jane@example.com',
        ];

        $attributeMap = new AttributeMap(
            mappings: [],
            requiredFields: ['email', 'sso_user_id']
        );

        $profile = $this->mapper->map($ssoAttributes, $attributeMap);

        // With empty mappings, should use direct attribute names
        $this->assertSame('user-456', $profile->ssoUserId);
        $this->assertSame('jane@example.com', $profile->email);
    }

    public function test_it_validates_required_attributes(): void
    {
        $ssoAttributes = [
            'sub' => 'user-123',
            // Missing email!
        ];

        $this->expectException(AttributeMappingException::class);
        $this->expectExceptionMessage('Required attribute');

        $this->mapper->validateRequiredAttributes($ssoAttributes, ['sub', 'email']);
    }

    public function test_it_passes_validation_when_all_required_present(): void
    {
        $ssoAttributes = [
            'sub' => 'user-123',
            'email' => 'user@example.com',
        ];

        // Should not throw
        $this->mapper->validateRequiredAttributes($ssoAttributes, ['sub', 'email']);
        
        $this->assertTrue(true); // Assert executed without exception
    }

    public function test_it_throws_when_required_field_missing_in_mapping(): void
    {
        $ssoAttributes = [
            'sub' => 'user-123',
            // Email is missing
        ];

        $attributeMap = new AttributeMap(
            mappings: [
                'sso_user_id' => 'sub',
                'email' => 'email', // Mapped but doesn't exist in ssoAttributes
            ],
            requiredFields: ['email', 'sso_user_id']
        );

        $this->expectException(AttributeMappingException::class);

        $this->mapper->map($ssoAttributes, $attributeMap);
    }

    public function test_it_handles_nested_attributes(): void
    {
        $ssoAttributes = [
            'id' => 'user-789',
            'emails' => [
                ['value' => 'primary@example.com', 'primary' => true],
                ['value' => 'secondary@example.com', 'primary' => false],
            ],
            'name' => [
                'givenName' => 'Alice',
                'familyName' => 'Smith',
            ],
        ];

        $attributeMap = new AttributeMap(
            mappings: [
                'sso_user_id' => 'id',
                'email' => 'emails.0.value', // Dot notation for nested
                'first_name' => 'name.givenName',
                'last_name' => 'name.familyName',
            ],
            requiredFields: ['email', 'sso_user_id']
        );

        $profile = $this->mapper->map($ssoAttributes, $attributeMap);

        $this->assertSame('user-789', $profile->ssoUserId);
        $this->assertSame('primary@example.com', $profile->email);
        $this->assertSame('Alice', $profile->firstName);
        $this->assertSame('Smith', $profile->lastName);
    }

    public function test_it_includes_unmapped_attributes(): void
    {
        $ssoAttributes = [
            'sub' => 'user-999',
            'email' => 'test@example.com',
            'custom_field' => 'custom_value',
            'department' => 'Engineering',
        ];

        $attributeMap = new AttributeMap(
            mappings: [
                'sso_user_id' => 'sub',
                'email' => 'email',
            ],
            requiredFields: ['email', 'sso_user_id']
        );

        $profile = $this->mapper->map($ssoAttributes, $attributeMap);

        // Unmapped attributes should be in attributes array
        $this->assertArrayHasKey('custom_field', $profile->attributes);
        $this->assertSame('custom_value', $profile->attributes['custom_field']);
        $this->assertArrayHasKey('department', $profile->attributes);
        $this->assertSame('Engineering', $profile->attributes['department']);
    }
}
