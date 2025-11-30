<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserVerificationRequirement::class)]
final class UserVerificationRequirementTest extends TestCase
{
    #[Test]
    public function it_has_required_case(): void
    {
        $this->assertSame('required', UserVerificationRequirement::REQUIRED->value);
    }

    #[Test]
    public function it_has_preferred_case(): void
    {
        $this->assertSame('preferred', UserVerificationRequirement::PREFERRED->value);
    }

    #[Test]
    public function it_has_discouraged_case(): void
    {
        $this->assertSame('discouraged', UserVerificationRequirement::DISCOURAGED->value);
    }

    #[Test]
    public function it_provides_description_for_required(): void
    {
        $description = UserVerificationRequirement::REQUIRED->description();
        
        $this->assertStringContainsString('Biometric', $description);
        $this->assertStringContainsString('required', $description);
    }

    #[Test]
    public function it_provides_description_for_preferred(): void
    {
        $description = UserVerificationRequirement::PREFERRED->description();
        
        $this->assertStringContainsString('Biometric', $description);
        $this->assertStringContainsString('preferred', $description);
    }

    #[Test]
    public function it_provides_description_for_discouraged(): void
    {
        $description = UserVerificationRequirement::DISCOURAGED->description();
        
        $this->assertStringContainsString('not needed', $description);
    }

    #[Test]
    public function it_checks_if_is_required(): void
    {
        $this->assertTrue(UserVerificationRequirement::REQUIRED->isRequired());
        $this->assertFalse(UserVerificationRequirement::PREFERRED->isRequired());
        $this->assertFalse(UserVerificationRequirement::DISCOURAGED->isRequired());
    }

    #[Test]
    public function it_checks_if_is_optional(): void
    {
        $this->assertFalse(UserVerificationRequirement::REQUIRED->isOptional());
        $this->assertTrue(UserVerificationRequirement::PREFERRED->isOptional());
        $this->assertTrue(UserVerificationRequirement::DISCOURAGED->isOptional());
    }

    #[Test]
    public function it_can_be_serialized_to_string(): void
    {
        $this->assertSame('required', UserVerificationRequirement::REQUIRED->value);
        $this->assertSame('preferred', UserVerificationRequirement::PREFERRED->value);
        $this->assertSame('discouraged', UserVerificationRequirement::DISCOURAGED->value);
    }
}
