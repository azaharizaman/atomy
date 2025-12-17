<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\RetentionCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetentionCategory::class)]
final class RetentionCategoryTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'customer', 'employee', 'financial', 'legal', 'marketing',
            'technical', 'transaction', 'medical', 'audit', 'temporary',
        ];

        $actualCases = array_map(fn(RetentionCategory $c) => $c->value, RetentionCategory::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[DataProvider('retentionMonthsProvider')]
    public function testGetDefaultRetentionMonthsReturnsExpectedValue(
        RetentionCategory $category,
        int $expectedMonths
    ): void {
        $this->assertSame($expectedMonths, $category->getDefaultRetentionMonths());
    }

    public static function retentionMonthsProvider(): array
    {
        return [
            'financial - 7 years' => [RetentionCategory::FINANCIAL, 84],
            'legal - 10 years' => [RetentionCategory::LEGAL, 120],
            'medical - 10 years' => [RetentionCategory::MEDICAL, 120],
            'employee - 7 years' => [RetentionCategory::EMPLOYEE, 84],
            'audit - 7 years' => [RetentionCategory::AUDIT, 84],
            'customer - 3 years' => [RetentionCategory::CUSTOMER, 36],
            'transaction - 5 years' => [RetentionCategory::TRANSACTION, 60],
            'marketing - 2 years' => [RetentionCategory::MARKETING, 24],
            'technical - 1 year' => [RetentionCategory::TECHNICAL, 12],
            'temporary - 1 month' => [RetentionCategory::TEMPORARY, 1],
        ];
    }

    #[DataProvider('secureDeletionProvider')]
    public function testRequiresSecureDeletionReturnsExpectedValue(
        RetentionCategory $category,
        bool $expected
    ): void {
        $this->assertSame($expected, $category->requiresSecureDeletion());
    }

    public static function secureDeletionProvider(): array
    {
        return [
            'financial requires' => [RetentionCategory::FINANCIAL, true],
            'legal requires' => [RetentionCategory::LEGAL, true],
            'medical requires' => [RetentionCategory::MEDICAL, true],
            'employee requires' => [RetentionCategory::EMPLOYEE, true],
            'customer requires' => [RetentionCategory::CUSTOMER, true],
            'marketing does not' => [RetentionCategory::MARKETING, false],
            'technical does not' => [RetentionCategory::TECHNICAL, false],
        ];
    }

    #[DataProvider('allCategoriesProvider')]
    public function testGetLabelReturnsString(RetentionCategory $category): void
    {
        $label = $category->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allCategoriesProvider(): array
    {
        return array_map(fn($c) => [$c], RetentionCategory::cases());
    }

    public function testTemporaryDoesNotAllowLegalHold(): void
    {
        $this->assertFalse(RetentionCategory::TEMPORARY->allowsLegalHold());
    }

    public function testFinancialAllowsLegalHold(): void
    {
        $this->assertTrue(RetentionCategory::FINANCIAL->allowsLegalHold());
    }
}
