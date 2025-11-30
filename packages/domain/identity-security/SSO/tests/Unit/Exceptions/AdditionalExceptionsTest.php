<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Exceptions;

use Nexus\SSO\Exceptions\SsoAuthenticationException;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use Nexus\SSO\Exceptions\SsoException;
use Nexus\SSO\Exceptions\SsoProviderException;
use Nexus\SSO\Exceptions\SsoSessionExpiredException;
use Nexus\SSO\Exceptions\TokenRefreshException;
use Nexus\SSO\Exceptions\UserProvisioningException;
use PHPUnit\Framework\TestCase;

final class AdditionalExceptionsTest extends TestCase
{
    public function test_sso_authentication_exception(): void
    {
        $exception = new SsoAuthenticationException('Authentication failed');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('Authentication failed', $exception->getMessage());
    }
    
    public function test_sso_configuration_exception(): void
    {
        $exception = new SsoConfigurationException('Invalid configuration');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('Invalid configuration', $exception->getMessage());
    }
    
    public function test_sso_provider_exception(): void
    {
        $exception = new SsoProviderException('azure', 'Connection timeout');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertStringContainsString('azure', $exception->getMessage());
        $this->assertStringContainsString('Connection timeout', $exception->getMessage());
    }
    
    public function test_sso_session_expired_exception(): void
    {
        $exception = new SsoSessionExpiredException('session-123');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertStringContainsString('session-123', $exception->getMessage());
    }
    
    public function test_token_refresh_exception(): void
    {
        $exception = new TokenRefreshException('Refresh token invalid');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('Refresh token invalid', $exception->getMessage());
    }
    
    public function test_user_provisioning_exception(): void
    {
        $exception = new UserProvisioningException('Failed to create user');
        
        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('Failed to create user', $exception->getMessage());
    }
}
