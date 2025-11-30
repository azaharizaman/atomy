<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use Nexus\Domain\Identity\ValueObjects\AuthenticatorAttachment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticatorAttachment::class)]
final class AuthenticatorAttachmentTest extends TestCase
{
    #[Test]
    public function it_has_platform_case(): void
    {
        $this->assertSame('platform', AuthenticatorAttachment::PLATFORM->value);
    }

    #[Test]
    public function it_has_cross_platform_case(): void
    {
        $this->assertSame('cross-platform', AuthenticatorAttachment::CROSS_PLATFORM->value);
    }

    #[Test]
    public function it_provides_description_for_platform(): void
    {
        $description = AuthenticatorAttachment::PLATFORM->description();
        
        $this->assertStringContainsString('Built-in', $description);
        $this->assertStringContainsString('Touch ID', $description);
    }

    #[Test]
    public function it_provides_description_for_cross_platform(): void
    {
        $description = AuthenticatorAttachment::CROSS_PLATFORM->description();
        
        $this->assertStringContainsString('External', $description);
        $this->assertStringContainsString('YubiKey', $description);
    }

    #[Test]
    public function it_checks_if_is_platform(): void
    {
        $this->assertTrue(AuthenticatorAttachment::PLATFORM->isPlatform());
        $this->assertFalse(AuthenticatorAttachment::CROSS_PLATFORM->isPlatform());
    }

    #[Test]
    public function it_checks_if_is_cross_platform(): void
    {
        $this->assertTrue(AuthenticatorAttachment::CROSS_PLATFORM->isCrossPlatform());
        $this->assertFalse(AuthenticatorAttachment::PLATFORM->isCrossPlatform());
    }

    #[Test]
    public function it_can_be_serialized_to_string(): void
    {
        $this->assertSame('platform', AuthenticatorAttachment::PLATFORM->value);
        $this->assertSame('cross-platform', AuthenticatorAttachment::CROSS_PLATFORM->value);
    }
}
