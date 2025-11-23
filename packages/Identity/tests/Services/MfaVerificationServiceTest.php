<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\Services;

use DateTimeImmutable;
use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Identity\Contracts\TotpManagerInterface;
use Nexus\Identity\Contracts\WebAuthnCredentialRepositoryInterface;
use Nexus\Identity\Contracts\WebAuthnManagerInterface;
use Nexus\Identity\Enums\MfaMethod;
use Nexus\Identity\Exceptions\MfaVerificationException;
use Nexus\Identity\Services\MfaVerificationService;
use Nexus\Identity\ValueObjects\BackupCode;
use Nexus\Identity\ValueObjects\TotpSecret;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Nexus\Identity\ValueObjects\WebAuthnCredential;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MfaVerificationService::class)]
final class MfaVerificationServiceTest extends TestCase
{
    private MfaEnrollmentRepositoryInterface&MockObject $enrollmentRepository;
    private WebAuthnCredentialRepositoryInterface&MockObject $credentialRepository;
    private TotpManagerInterface&MockObject $totpManager;
    private WebAuthnManagerInterface&MockObject $webAuthnManager;
    private CacheRepositoryInterface&MockObject $cache;
    private LoggerInterface&MockObject $logger;
    private MfaVerificationService $service;

    protected function setUp(): void
    {
        $this->enrollmentRepository = $this->createMock(MfaEnrollmentRepositoryInterface::class);
        $this->credentialRepository = $this->createMock(WebAuthnCredentialRepositoryInterface::class);
        $this->totpManager = $this->createMock(TotpManagerInterface::class);
        $this->webAuthnManager = $this->createMock(WebAuthnManagerInterface::class);
        $this->cache = $this->createMock(CacheRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MfaVerificationService(
            $this->enrollmentRepository,
            $this->credentialRepository,
            $this->totpManager,
            $this->webAuthnManager,
            $this->cache,
            $this->logger
        );
    }

    #[Test]
    public function verifies_totp_code_successfully(): void
    {
        $userId = 'user123';
        $code = '123456';
        $enrollmentId = 'enrollment123';
        $secret = TotpSecret::generate();

        $this->cache
            ->method('get')
            ->willReturn(0); // Not rate limited

        $this->enrollmentRepository
            ->method('findActiveByUserAndMethod')
            ->with($userId, MfaMethod::TOTP->value)
            ->willReturn([
                'id' => $enrollmentId,
                'secret' => $secret->toArray(),
            ]);

        $this->totpManager
            ->method('verifyCode')
            ->with($this->anything(), $code)
            ->willReturn(true);

        $this->cache
            ->expects($this->once())
            ->method('forget'); // Clear rate limit on success

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('updateLastUsed')
            ->with($enrollmentId, $this->isInstanceOf(DateTimeImmutable::class));

        $result = $this->service->verifyTotp($userId, $code);

        $this->assertTrue($result);
    }

    #[Test]
    public function throws_exception_for_invalid_totp_code(): void
    {
        $userId = 'user123';
        $code = '999999';
        $secret = TotpSecret::generate();

        $this->cache
            ->method('get')
            ->willReturn(0);

        $this->enrollmentRepository
            ->method('findActiveByUserAndMethod')
            ->willReturn([
                'id' => 'enrollment123',
                'secret' => $secret->toArray(),
            ]);

        $this->totpManager
            ->method('verifyCode')
            ->willReturn(false);

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('Invalid TOTP code');

        $this->service->verifyTotp($userId, $code);
    }

    #[Test]
    public function throws_exception_when_totp_rate_limited(): void
    {
        $userId = 'user123';
        $code = '123456';

        $this->cache
            ->method('get')
            ->willReturn(5); // Max attempts reached

        $this->cache
            ->method('getTtl')
            ->willReturn(300); // 5 minutes remaining

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('rate limited');

        $this->service->verifyTotp($userId, $code);
    }

    #[Test]
    public function throws_exception_when_totp_not_enrolled(): void
    {
        $userId = 'user123';
        $code = '123456';

        $this->cache
            ->method('get')
            ->willReturn(0);

        $this->enrollmentRepository
            ->method('findActiveByUserAndMethod')
            ->willReturn(null);

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('does not have totp enrolled');

        $this->service->verifyTotp($userId, $code);
    }

    #[Test]
    public function generates_webauthn_authentication_options_for_user(): void
    {
        $userId = 'user123';

        $this->credentialRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['credential_id' => 'cred1'],
                ['credential_id' => 'cred2'],
            ]);

        $expectedOptions = WebAuthnAuthenticationOptions::forUser(
            challenge: base64_encode(random_bytes(32)),
            rpId: 'example.com',
            allowCredentials: ['cred1', 'cred2']
        );

        $this->webAuthnManager
            ->method('generateAuthenticationOptions')
            ->willReturn($expectedOptions);

        $result = $this->service->generateWebAuthnAuthenticationOptions($userId);

        $this->assertInstanceOf(WebAuthnAuthenticationOptions::class, $result);
    }

    #[Test]
    public function generates_webauthn_authentication_options_for_usernameless(): void
    {
        $expectedOptions = WebAuthnAuthenticationOptions::usernameless(
            challenge: base64_encode(random_bytes(32)),
            rpId: 'example.com'
        );

        $this->webAuthnManager
            ->method('generateAuthenticationOptions')
            ->with([], false)
            ->willReturn($expectedOptions);

        $result = $this->service->generateWebAuthnAuthenticationOptions(null);

        $this->assertInstanceOf(WebAuthnAuthenticationOptions::class, $result);
    }

    #[Test]
    public function throws_exception_when_no_credentials_for_webauthn(): void
    {
        $userId = 'user123';

        $this->credentialRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([]);

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('does not have passkey enrolled');

        $this->service->generateWebAuthnAuthenticationOptions($userId);
    }

    #[Test]
    public function verifies_webauthn_successfully(): void
    {
        $userId = 'user123';
        $credentialId = 'cred123';
        $assertionJson = json_encode(['id' => $credentialId, 'response' => []]);
        $challenge = base64_encode('challenge');
        $origin = 'https://example.com';

        $this->cache
            ->method('get')
            ->willReturn(0); // Not rate limited

        $credential = WebAuthnCredential::create(
            credentialId: $credentialId,
            publicKey: base64_encode('publicKey'),
            signCount: 0,
            transports: ['usb'],
            aaguid: '00000000-0000-0000-0000-000000000000'
        );

        $this->credentialRepository
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn([
                'user_id' => $userId,
                'credential_id' => $credentialId,
                'public_key' => $credential->publicKey,
                'sign_count' => $credential->signCount,
                'transports' => $credential->transports,
                'aaguid' => $credential->aaguid,
                'friendly_name' => 'Test Key',
                'last_used_at' => new DateTimeImmutable(),
            ]);

        $this->webAuthnManager
            ->method('verifyAuthentication')
            ->willReturn([
                'credentialId' => $credentialId,
                'newSignCount' => 1,
                'userHandle' => $userId,
            ]);

        $this->credentialRepository
            ->expects($this->once())
            ->method('updateAfterAuthentication')
            ->with($credentialId, 1, $this->isInstanceOf(DateTimeImmutable::class));

        $result = $this->service->verifyWebAuthn($assertionJson, $challenge, $origin, $userId);

        $this->assertEquals($userId, $result['userId']);
        $this->assertEquals($credentialId, $result['credentialId']);
    }

    #[Test]
    public function verifies_backup_code_successfully(): void
    {
        $userId = 'user123';
        $code = 'ABCD-EFGH-IJ';
        $backupCode = BackupCode::generate();
        $enrollmentId = 'enrollment123';

        $this->cache
            ->method('get')
            ->willReturn(0);

        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->with($userId)
            ->willReturn([
                [
                    'id' => $enrollmentId,
                    'secret' => [
                        'hash' => BackupCode::hash($code),
                        'consumed_at' => null,
                    ],
                ],
            ]);

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('consumeBackupCode')
            ->with($enrollmentId, $this->isInstanceOf(DateTimeImmutable::class));

        $result = $this->service->verifyBackupCode($userId, $code);

        $this->assertTrue($result);
    }

    #[Test]
    public function throws_exception_for_invalid_backup_code(): void
    {
        $userId = 'user123';
        $code = 'WRONG-CODE-XX';

        $this->cache
            ->method('get')
            ->willReturn(0);

        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->willReturn([
                [
                    'id' => 'enrollment123',
                    'secret' => [
                        'hash' => BackupCode::hash('DIFFERENT-CODE'),
                        'consumed_at' => null,
                    ],
                ],
            ]);

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('Invalid backup code');

        $this->service->verifyBackupCode($userId, $code);
    }

    #[Test]
    public function skips_consumed_backup_codes(): void
    {
        $userId = 'user123';
        $code = 'ABCD-EFGH-IJ';

        $this->cache
            ->method('get')
            ->willReturn(0);

        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->willReturn([
                [
                    'id' => 'enrollment123',
                    'secret' => [
                        'hash' => BackupCode::hash($code),
                        'consumed_at' => new DateTimeImmutable(), // Already consumed
                    ],
                ],
            ]);

        $this->expectException(MfaVerificationException::class);
        $this->expectExceptionMessage('Invalid backup code');

        $this->service->verifyBackupCode($userId, $code);
    }

    #[Test]
    public function verifies_with_fallback_chain(): void
    {
        $userId = 'user123';
        $backupCode = 'ABCD-EFGH-IJ';

        $this->cache
            ->method('get')
            ->willReturn(0);

        // TOTP will fail
        $this->enrollmentRepository
            ->method('findActiveByUserAndMethod')
            ->with($userId, MfaMethod::TOTP->value)
            ->willReturn(null);

        // Backup code will succeed
        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->willReturn([
                [
                    'id' => 'enrollment123',
                    'secret' => [
                        'hash' => BackupCode::hash($backupCode),
                        'consumed_at' => null,
                    ],
                ],
            ]);

        $result = $this->service->verifyWithFallback($userId, [
            'totp' => '999999',
            'backup_code' => $backupCode,
        ]);

        $this->assertEquals('backup_code', $result['method']);
        $this->assertTrue($result['verified']);
    }

    #[Test]
    public function checks_rate_limit_status(): void
    {
        $userId = 'user123';
        $method = MfaMethod::TOTP->value;

        $this->cache
            ->method('get')
            ->with("mfa:rate_limit:{$userId}:{$method}", 0)
            ->willReturn(5);

        $result = $this->service->isRateLimited($userId, $method);

        $this->assertTrue($result);
    }

    #[Test]
    public function gets_remaining_backup_codes_count(): void
    {
        $userId = 'user123';

        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->willReturn([
                ['secret' => ['consumed_at' => null]],
                ['secret' => ['consumed_at' => null]],
                ['secret' => ['consumed_at' => new DateTimeImmutable()]], // Consumed
            ]);

        $result = $this->service->getRemainingBackupCodesCount($userId);

        $this->assertEquals(2, $result);
    }

    #[Test]
    public function recommends_backup_code_regeneration(): void
    {
        $userId = 'user123';

        $this->enrollmentRepository
            ->method('findActiveBackupCodes')
            ->willReturn([
                ['secret' => ['consumed_at' => null]],
                ['secret' => ['consumed_at' => null]],
            ]);

        $result = $this->service->shouldRegenerateBackupCodes($userId);

        $this->assertTrue($result); // ≤2 remaining
    }

    #[Test]
    public function records_verification_attempts(): void
    {
        $userId = 'user123';
        $method = MfaMethod::TOTP->value;

        $this->cache
            ->expects($this->once())
            ->method('put')
            ->with(
                "mfa:rate_limit:{$userId}:{$method}",
                1,
                900 // 15 minutes
            );

        $this->service->recordVerificationAttempt($userId, $method, false);
    }

    #[Test]
    public function clears_rate_limit_on_successful_verification(): void
    {
        $userId = 'user123';
        $method = MfaMethod::TOTP->value;

        $this->cache
            ->expects($this->once())
            ->method('forget')
            ->with("mfa:rate_limit:{$userId}:{$method}");

        $this->service->recordVerificationAttempt($userId, $method, true);
    }

    #[Test]
    public function clears_rate_limit_manually(): void
    {
        $userId = 'user123';
        $method = MfaMethod::TOTP->value;

        $this->cache
            ->expects($this->once())
            ->method('forget')
            ->with("mfa:rate_limit:{$userId}:{$method}");

        $result = $this->service->clearRateLimit($userId, $method);

        $this->assertTrue($result);
    }
}
