<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use Nexus\Domain\Identity\ValueObjects\AttestationConveyancePreference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttestationConveyancePreference::class)]
final class AttestationConveyancePreferenceTest extends TestCase
{
    #[Test]
    public function it_has_none_case(): void
    {
        $this->assertSame('none', AttestationConveyancePreference::NONE->value);
    }

    #[Test]
    public function it_has_indirect_case(): void
    {
        $this->assertSame('indirect', AttestationConveyancePreference::INDIRECT->value);
    }

    #[Test]
    public function it_has_direct_case(): void
    {
        $this->assertSame('direct', AttestationConveyancePreference::DIRECT->value);
    }

    #[Test]
    public function it_has_enterprise_case(): void
    {
        $this->assertSame('enterprise', AttestationConveyancePreference::ENTERPRISE->value);
    }

    #[Test]
    public function it_provides_description_for_none(): void
    {
        $description = AttestationConveyancePreference::NONE->description();
        
        $this->assertStringContainsString('No attestation', $description);
        $this->assertStringContainsString('privacy', $description);
    }

    #[Test]
    public function it_provides_description_for_indirect(): void
    {
        $description = AttestationConveyancePreference::INDIRECT->description();
        
        $this->assertStringContainsString('Anonymized', $description);
    }

    #[Test]
    public function it_provides_description_for_direct(): void
    {
        $description = AttestationConveyancePreference::DIRECT->description();
        
        $this->assertStringContainsString('Full attestation', $description);
        $this->assertStringContainsString('verification', $description);
    }

    #[Test]
    public function it_provides_description_for_enterprise(): void
    {
        $description = AttestationConveyancePreference::ENTERPRISE->description();
        
        $this->assertStringContainsString('Enterprise', $description);
        $this->assertStringContainsString('managed', $description);
    }

    #[Test]
    public function it_checks_if_requires_validation(): void
    {
        $this->assertFalse(AttestationConveyancePreference::NONE->requiresValidation());
        $this->assertTrue(AttestationConveyancePreference::INDIRECT->requiresValidation());
        $this->assertTrue(AttestationConveyancePreference::DIRECT->requiresValidation());
        $this->assertTrue(AttestationConveyancePreference::ENTERPRISE->requiresValidation());
    }

    #[Test]
    public function it_checks_if_is_privacy_preserving(): void
    {
        $this->assertTrue(AttestationConveyancePreference::NONE->isPrivacyPreserving());
        $this->assertTrue(AttestationConveyancePreference::INDIRECT->isPrivacyPreserving());
        $this->assertFalse(AttestationConveyancePreference::DIRECT->isPrivacyPreserving());
        $this->assertFalse(AttestationConveyancePreference::ENTERPRISE->isPrivacyPreserving());
    }

    #[Test]
    public function it_can_be_serialized_to_string(): void
    {
        $this->assertSame('none', AttestationConveyancePreference::NONE->value);
        $this->assertSame('indirect', AttestationConveyancePreference::INDIRECT->value);
        $this->assertSame('direct', AttestationConveyancePreference::DIRECT->value);
        $this->assertSame('enterprise', AttestationConveyancePreference::ENTERPRISE->value);
    }
}
