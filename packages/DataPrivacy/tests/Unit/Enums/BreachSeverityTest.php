<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\BreachSeverity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BreachSeverity::class)]
final class BreachSeverityTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = ['low', 'medium', 'high', 'critical'];
        $actualCases = array_map(fn(BreachSeverity $s) => $s->value, BreachSeverity::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[DataProvider('allSeveritiesProvider')]
    public function testGetLabelReturnsString(BreachSeverity $severity): void
    {
        $label = $severity->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allSeveritiesProvider(): array
    {
        return array_map(fn($s) => [$s], BreachSeverity::cases());
    }

    public function testCriticalRequiresImmediateEscalation(): void
    {
        $this->assertTrue(BreachSeverity::CRITICAL->requiresImmediateEscalation());
    }

    public function testHighDoesNotRequireImmediateEscalation(): void
    {
        // Only CRITICAL requires immediate escalation
        $this->assertFalse(BreachSeverity::HIGH->requiresImmediateEscalation());
    }

    public function testLowDoesNotRequireImmediateEscalation(): void
    {
        $this->assertFalse(BreachSeverity::LOW->requiresImmediateEscalation());
    }

    public function testSeverityScoreOrderIsCorrect(): void
    {
        $this->assertLessThan(
            BreachSeverity::HIGH->getScore(),
            BreachSeverity::MEDIUM->getScore()
        );
        $this->assertLessThan(
            BreachSeverity::CRITICAL->getScore(),
            BreachSeverity::HIGH->getScore()
        );
    }
}
