<?php

declare(strict_types=1);

namespace Nexus\ESG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\ESG\Services\CertificationValidator;
use Nexus\ESG\ValueObjects\CertificationMetadata;
use Nexus\Common\Contracts\ClockInterface;

final class CertificationValidatorTest extends TestCase
{
    private $clock;
    private $validator;

    protected function setUp(): void
    {
        $this->clock = $this->createMock(ClockInterface::class);
        $this->validator = new CertificationValidator($this->clock);
    }

    public function test_validates_active_certificate(): void
    {
        $now = new \DateTimeImmutable('2026-03-02');
        $this->clock->method('now')->willReturn($now);

        $cert = new CertificationMetadata(
            'ISO', 'ISO 14001', '123',
            new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2027-01-01')
        );

        $this->assertTrue($this->validator->isValid($cert));
        $this->assertGreaterThan(0, $this->validator->getDaysUntilExpiry($cert));
    }

    public function test_identifies_expired_certificate(): void
    {
        $now = new \DateTimeImmutable('2026-03-02');
        $this->clock->method('now')->willReturn($now);

        $cert = new CertificationMetadata(
            'ISO', 'ISO 14001', '123',
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2025-12-31')
        );

        $this->assertFalse($this->validator->isValid($cert));
        $this->assertEquals(0, $this->validator->getDaysUntilExpiry($cert));
    }
}
