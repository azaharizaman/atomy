<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\SSO\ValueObjects\SsoSession;
use Nexus\SSO\ValueObjects\UserProfile;
use PHPUnit\Framework\TestCase;

final class SsoSessionTest extends TestCase
{
    public function test_can_create_sso_session(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com',
            firstName: 'John',
            lastName: 'Doe'
        );
        
        $createdAt = new DateTimeImmutable('2025-11-24 10:00:00');
        $expiresAt = new DateTimeImmutable('2025-11-24 18:00:00');
        
        $session = new SsoSession(
            sessionId: 'session-abc-123',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'access-token-xyz',
            refreshToken: 'refresh-token-abc',
            createdAt: $createdAt,
            expiresAt: $expiresAt
        );
        
        $this->assertSame('session-abc-123', $session->sessionId);
        $this->assertSame('azure', $session->providerName);
        $this->assertSame($userProfile, $session->userProfile);
        $this->assertSame('access-token-xyz', $session->accessToken);
        $this->assertSame('refresh-token-abc', $session->refreshToken);
        $this->assertSame($createdAt, $session->createdAt);
        $this->assertSame($expiresAt, $session->expiresAt);
    }
    
    public function test_optional_refresh_token(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com'
        );
        
        $session = new SsoSession(
            sessionId: 'session-123',
            providerName: 'google',
            userProfile: $userProfile,
            accessToken: 'access-token',
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+1 hour')
        );
        
        $this->assertNull($session->refreshToken);
    }
    
    public function test_can_check_if_session_is_expired(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com'
        );
        
        $expiredSession = new SsoSession(
            sessionId: 'session-expired',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'token',
            createdAt: new DateTimeImmutable('-2 hours'),
            expiresAt: new DateTimeImmutable('-1 hour')
        );
        
        $this->assertTrue($expiredSession->isExpired());
        
        $activeSession = new SsoSession(
            sessionId: 'session-active',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'token',
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+1 hour')
        );
        
        $this->assertFalse($activeSession->isExpired());
    }
    
    public function test_can_check_time_until_expiry(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com'
        );
        
        $session = new SsoSession(
            sessionId: 'session-123',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'token',
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+3600 seconds')
        );
        
        $secondsUntilExpiry = $session->getSecondsUntilExpiry();
        
        // Should be approximately 3600 seconds (allow 2 second tolerance for test execution)
        $this->assertGreaterThanOrEqual(3598, $secondsUntilExpiry);
        $this->assertLessThanOrEqual(3600, $secondsUntilExpiry);
    }
    
    public function test_expired_session_returns_zero_for_seconds_until_expiry(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com'
        );
        
        $expiredSession = new SsoSession(
            sessionId: 'session-expired',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'token',
            createdAt: new DateTimeImmutable('-2 hours'),
            expiresAt: new DateTimeImmutable('-1 hour')
        );
        
        $this->assertSame(0, $expiredSession->getSecondsUntilExpiry());
    }
    
    public function test_session_is_immutable(): void
    {
        $userProfile = new UserProfile(
            ssoUserId: 'user-123',
            email: 'user@example.com'
        );
        
        $session = new SsoSession(
            sessionId: 'session-123',
            providerName: 'azure',
            userProfile: $userProfile,
            accessToken: 'token',
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+1 hour')
        );
        
        $this->expectException(\Error::class);
        $session->accessToken = 'modified-token';
    }
}
