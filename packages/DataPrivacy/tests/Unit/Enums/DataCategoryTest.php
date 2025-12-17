<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\DataCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataCategory::class)]
final class DataCategoryTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'personal', 'contact', 'financial', 'health', 'biometric',
            'genetic', 'racial_ethnic', 'political', 'religious',
            'trade_union', 'sexual', 'criminal', 'children', 'location',
            'online_identifiers', 'employment', 'education', 'behavioral',
        ];

        $actualCases = array_map(fn(DataCategory $c) => $c->value, DataCategory::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    public function testSensitiveCategoriesAreSensitive(): void
    {
        $sensitiveCategories = [
            DataCategory::HEALTH,
            DataCategory::BIOMETRIC,
            DataCategory::GENETIC,
            DataCategory::RACIAL_ETHNIC,
            DataCategory::POLITICAL,
            DataCategory::RELIGIOUS,
            DataCategory::SEXUAL,
            DataCategory::CRIMINAL,
        ];

        foreach ($sensitiveCategories as $category) {
            $this->assertTrue($category->isSensitive(), "{$category->value} should be sensitive");
        }
    }

    public function testNonSensitiveCategoriesAreNotSensitive(): void
    {
        $nonSensitiveCategories = [
            DataCategory::PERSONAL,
            DataCategory::CONTACT,
            DataCategory::EMPLOYMENT,
        ];

        foreach ($nonSensitiveCategories as $category) {
            $this->assertFalse($category->isSensitive(), "{$category->value} should not be sensitive");
        }
    }

    #[DataProvider('allCategoriesProvider')]
    public function testGetLabelReturnsString(DataCategory $category): void
    {
        $label = $category->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allCategoriesProvider(): array
    {
        return array_map(fn($c) => [$c], DataCategory::cases());
    }

    public function testHealthRequiresExplicitConsent(): void
    {
        $this->assertTrue(DataCategory::HEALTH->requiresExplicitConsent());
    }

    public function testContactDoesNotRequireExplicitConsent(): void
    {
        $this->assertFalse(DataCategory::CONTACT->requiresExplicitConsent());
    }
}
