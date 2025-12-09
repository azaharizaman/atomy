<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use PHPUnit\Framework\TestCase;

final class PolicyViolationSeverityTest extends TestCase
{
    public function test_info_severity_is_not_blocking(): void
    {
        $this->assertFalse(PolicyViolationSeverity::INFO->isBlocking());
    }

    public function test_warning_severity_is_not_blocking(): void
    {
        $this->assertFalse(PolicyViolationSeverity::WARNING->isBlocking());
    }

    public function test_error_severity_is_blocking(): void
    {
        $this->assertTrue(PolicyViolationSeverity::ERROR->isBlocking());
    }

    public function test_critical_severity_is_blocking(): void
    {
        $this->assertTrue(PolicyViolationSeverity::CRITICAL->isBlocking());
    }

    public function test_info_severity_does_not_require_override(): void
    {
        $this->assertFalse(PolicyViolationSeverity::INFO->requiresOverride());
    }

    public function test_warning_severity_requires_override(): void
    {
        $this->assertTrue(PolicyViolationSeverity::WARNING->requiresOverride());
    }

    public function test_error_severity_requires_override(): void
    {
        $this->assertTrue(PolicyViolationSeverity::ERROR->requiresOverride());
    }

    public function test_critical_severity_requires_override(): void
    {
        $this->assertTrue(PolicyViolationSeverity::CRITICAL->requiresOverride());
    }

    public function test_weights_are_correctly_ordered(): void
    {
        $this->assertSame(1, PolicyViolationSeverity::INFO->getWeight());
        $this->assertSame(2, PolicyViolationSeverity::WARNING->getWeight());
        $this->assertSame(3, PolicyViolationSeverity::ERROR->getWeight());
        $this->assertSame(4, PolicyViolationSeverity::CRITICAL->getWeight());

        // Verify ordering
        $this->assertLessThan(
            PolicyViolationSeverity::WARNING->getWeight(),
            PolicyViolationSeverity::INFO->getWeight()
        );
        $this->assertLessThan(
            PolicyViolationSeverity::ERROR->getWeight(),
            PolicyViolationSeverity::WARNING->getWeight()
        );
        $this->assertLessThan(
            PolicyViolationSeverity::CRITICAL->getWeight(),
            PolicyViolationSeverity::ERROR->getWeight()
        );
    }

    public function test_all_severities_have_labels(): void
    {
        foreach (PolicyViolationSeverity::cases() as $severity) {
            $this->assertNotEmpty($severity->getLabel());
        }
    }

    public function test_severity_labels_are_human_readable(): void
    {
        $this->assertSame('Information', PolicyViolationSeverity::INFO->getLabel());
        $this->assertSame('Warning', PolicyViolationSeverity::WARNING->getLabel());
        $this->assertSame('Error', PolicyViolationSeverity::ERROR->getLabel());
        $this->assertSame('Critical', PolicyViolationSeverity::CRITICAL->getLabel());
    }
}
