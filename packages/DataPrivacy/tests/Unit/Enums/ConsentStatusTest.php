<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\ConsentStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConsentStatus::class)]
final class ConsentStatusTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = ['granted', 'withdrawn', 'expired', 'pending', 'denied'];
        $actualCases = array_map(fn(ConsentStatus $s) => $s->value, ConsentStatus::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    public function testGrantedIsActive(): void
    {
        $this->assertTrue(ConsentStatus::GRANTED->isActive());
    }

    public function testWithdrawnIsNotActive(): void
    {
        $this->assertFalse(ConsentStatus::WITHDRAWN->isActive());
    }

    public function testExpiredIsNotActive(): void
    {
        $this->assertFalse(ConsentStatus::EXPIRED->isActive());
    }

    public function testPendingIsNotActive(): void
    {
        $this->assertFalse(ConsentStatus::PENDING->isActive());
    }

    public function testDeniedIsNotActive(): void
    {
        $this->assertFalse(ConsentStatus::DENIED->isActive());
    }

    #[DataProvider('allStatusesProvider')]
    public function testGetLabelReturnsString(ConsentStatus $status): void
    {
        $label = $status->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allStatusesProvider(): array
    {
        return array_map(fn($s) => [$s], ConsentStatus::cases());
    }
}
