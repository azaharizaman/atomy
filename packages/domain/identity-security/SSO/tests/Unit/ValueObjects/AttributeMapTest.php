<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\SSO\ValueObjects\AttributeMap;

/**
 * Test for AttributeMap value object
 * 
 * TDD Cycle 4: RED phase
 */
final class AttributeMapTest extends TestCase
{
    public function test_it_can_be_created_with_mappings(): void
    {
        $map = new AttributeMap(
            mappings: [
                'email' => 'mail',
                'first_name' => 'givenName',
                'last_name' => 'surname',
            ]
        );

        $this->assertSame([
            'email' => 'mail',
            'first_name' => 'givenName',
            'last_name' => 'surname',
        ], $map->mappings);
    }

    public function test_it_has_default_required_fields(): void
    {
        $map = new AttributeMap(mappings: []);

        $this->assertSame(['email', 'sso_user_id'], $map->requiredFields);
    }

    public function test_it_can_have_custom_required_fields(): void
    {
        $map = new AttributeMap(
            mappings: [],
            requiredFields: ['email', 'sso_user_id', 'first_name']
        );

        $this->assertSame(['email', 'sso_user_id', 'first_name'], $map->requiredFields);
    }

    public function test_it_is_immutable(): void
    {
        $map = new AttributeMap(
            mappings: ['email' => 'mail']
        );

        $this->expectException(\Error::class);
        $map->mappings = ['modified' => 'value'];
    }
}
