<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\SSO\ValueObjects\SsoProtocol;

/**
 * Test for SsoProtocol enum
 * 
 * TDD Cycle 1: RED phase
 */
final class SsoProtocolTest extends TestCase
{
    public function test_it_has_saml2_protocol(): void
    {
        $protocol = SsoProtocol::SAML2;
        
        $this->assertInstanceOf(SsoProtocol::class, $protocol);
        $this->assertSame('saml2', $protocol->value);
    }

    public function test_it_has_oauth2_protocol(): void
    {
        $protocol = SsoProtocol::OAuth2;
        
        $this->assertInstanceOf(SsoProtocol::class, $protocol);
        $this->assertSame('oauth2', $protocol->value);
    }

    public function test_it_has_oidc_protocol(): void
    {
        $protocol = SsoProtocol::OIDC;
        
        $this->assertInstanceOf(SsoProtocol::class, $protocol);
        $this->assertSame('oidc', $protocol->value);
    }

    public function test_it_can_be_created_from_string(): void
    {
        $protocol = SsoProtocol::from('saml2');
        
        $this->assertSame(SsoProtocol::SAML2, $protocol);
    }

    public function test_all_protocols_are_available(): void
    {
        $protocols = SsoProtocol::cases();
        
        $this->assertCount(3, $protocols);
        $this->assertContains(SsoProtocol::SAML2, $protocols);
        $this->assertContains(SsoProtocol::OAuth2, $protocols);
        $this->assertContains(SsoProtocol::OIDC, $protocols);
    }
}
