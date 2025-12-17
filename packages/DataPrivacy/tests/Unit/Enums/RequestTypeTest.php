<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\RequestType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestType::class)]
final class RequestTypeTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'access', 'erasure', 'rectification', 'restriction',
            'portability', 'objection', 'automated_decision',
        ];

        $actualCases = array_map(fn(RequestType $t) => $t->value, RequestType::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[DataProvider('labelProvider')]
    public function testGetLabelReturnsString(RequestType $type): void
    {
        $label = $type->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function labelProvider(): array
    {
        return array_map(fn($t) => [$t], RequestType::cases());
    }

    #[DataProvider('deadlineProvider')]
    public function testGetDefaultDeadlineDaysReturnsPositiveInteger(RequestType $type): void
    {
        $days = $type->getDefaultDeadlineDays();
        $this->assertGreaterThan(0, $days);
    }

    public static function deadlineProvider(): array
    {
        return array_map(fn($t) => [$t], RequestType::cases());
    }

    public function testAccessRequestHasLabel(): void
    {
        $this->assertNotEmpty(RequestType::ACCESS->getLabel());
    }

    public function testErasureRequestHasLabel(): void
    {
        $this->assertNotEmpty(RequestType::ERASURE->getLabel());
    }
}
