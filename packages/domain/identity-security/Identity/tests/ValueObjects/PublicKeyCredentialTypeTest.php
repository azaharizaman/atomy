<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use Nexus\Identity\ValueObjects\PublicKeyCredentialType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicKeyCredentialType::class)]
final class PublicKeyCredentialTypeTest extends TestCase
{
    #[Test]
    public function it_has_public_key_case(): void
    {
        $this->assertSame('public-key', PublicKeyCredentialType::PUBLIC_KEY->value);
    }

    #[Test]
    public function it_provides_description(): void
    {
        $description = PublicKeyCredentialType::PUBLIC_KEY->description();
        
        $this->assertStringContainsString('WebAuthn', $description);
        $this->assertStringContainsString('public key', $description);
    }

    #[Test]
    public function it_can_be_serialized_to_string(): void
    {
        $this->assertSame('public-key', PublicKeyCredentialType::PUBLIC_KEY->value);
    }
}
