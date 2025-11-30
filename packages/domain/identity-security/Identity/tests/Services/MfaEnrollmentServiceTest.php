<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\Services;

use DateTimeImmutable;
use Nexus\Identity\Contracts\MfaEnrollmentDataInterface;
use Nexus\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Identity\Contracts\TotpManagerInterface;
use Nexus\Identity\Contracts\WebAuthnCredentialRepositoryInterface;
use Nexus\Identity\Contracts\WebAuthnManagerInterface;
use Nexus\Identity\ValueObjects\MfaMethod;
use Nexus\Identity\Exceptions\MfaEnrollmentException;
use Nexus\Identity\Services\MfaEnrollmentService;
use Nexus\Identity\ValueObjects\BackupCode;
use Nexus\Identity\ValueObjects\BackupCodeSet;
use Nexus\Identity\ValueObjects\TotpSecret;
use Nexus\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Identity\ValueObjects\WebAuthnRegistrationOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MfaEnrollmentService::class)]
final class MfaEnrollmentServiceTest extends TestCase
{
    private MfaEnrollmentRepositoryInterface&MockObject $enrollmentRepository;
    private WebAuthnCredentialRepositoryInterface&MockObject $credentialRepository;
    private TotpManagerInterface&MockObject $totpManager;
    private WebAuthnManagerInterface&MockObject $webAuthnManager;
    private LoggerInterface&MockObject $logger;
    private MfaEnrollmentService $service;

    protected function setUp(): void
    {
        $this->enrollmentRepository = $this->createMock(MfaEnrollmentRepositoryInterface::class);
        $this->credentialRepository = $this->createMock(WebAuthnCredentialRepositoryInterface::class);
        $this->totpManager = $this->createMock(TotpManagerInterface::class);
        $this->webAuthnManager = $this->createMock(WebAuthnManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MfaEnrollmentService(
            $this->enrollmentRepository,
            $this->credentialRepository,
            $this->totpManager,
            $this->webAuthnManager,
            $this->logger
        );
    }

    #[Test]
    public function enrolls_totp_successfully(): void
    {
        $userId = 'user123';
        $secret = TotpSecret::generate();
        $qrUri = 'otpauth://totp/Nexus:user@example.com?secret=ABC&issuer=Nexus';
        $qrDataUrl = 'data:image/png;base64,iVBOR...';

        $this->enrollmentRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->totpManager
            ->method('generateSecret')
            ->willReturn($secret);

        $this->totpManager
            ->method('generateQrCodeUri')
            ->willReturn($qrUri);

        $this->totpManager
            ->method('generateQrCodeImage')
            ->willReturn($qrDataUrl);

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($userId) {
                return $data['user_id'] === $userId
                    && $data['method'] === MfaMethod::TOTP->value
                    && $data['is_active'] === false;
            }));

        $result = $this->service->enrollTotp($userId);

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('qrCodeUri', $result);
        $this->assertArrayHasKey('qrCodeDataUrl', $result);
        $this->assertSame($qrUri, $result['qrCodeUri']);
        $this->assertSame($qrDataUrl, $result['qrCodeDataUrl']);
    }

    #[Test]
    public function throws_exception_when_totp_already_enrolled(): void
    {
        $userId = 'user123';

        $this->enrollmentRepository
            ->method('findByUserId')
            ->willReturn([
                [
                    'user_id' => $userId,
                    'method' => MfaMethod::TOTP->value,
                    'is_active' => true,
                ],
            ]);

        $this->expectException(MfaEnrollmentException::class);
        $this->expectExceptionMessage('already has TOTP enrolled');

        $this->service->enrollTotp($userId);
    }

    #[Test]
    public function verifies_totp_enrollment_successfully(): void
    {
        $userId = 'user123';
        $code = '123456';
        $enrollmentId = 'enrollment123';
        $secret = TotpSecret::generate();

        // Create mock enrollment
        $enrollment = $this->createMock(MfaEnrollmentDataInterface::class);
        $enrollment->method('getId')->willReturn($enrollmentId);
        $enrollment->method('getSecret')->willReturn($secret->toArray());

        $this->enrollmentRepository
            ->method('findPendingByUserAndMethod')
            ->with($userId, MfaMethod::TOTP)
            ->willReturn($enrollment);

        $this->totpManager
            ->method('verifyCode')
            ->with($this->anything(), $code)
            ->willReturn(true);

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('activate')
            ->with($enrollmentId);

        $result = $this->service->verifyTotpEnrollment($userId, $code);

        $this->assertTrue($result);
    }

    #[Test]
    public function generates_webauthn_registration_options(): void
    {
        $userId = 'user123';
        $userName = 'user@example.com';
        $userDisplayName = 'Test User';

        $this->credentialRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['credential_id' => 'cred1'],
                ['credential_id' => 'cred2'],
            ]);

        $expectedOptions = WebAuthnRegistrationOptions::create(
            challenge: base64_encode(random_bytes(32)),
            rpId: 'example.com',
            rpName: 'Nexus ERP',
            userId: $userId,
            userName: $userName,
            userDisplayName: $userDisplayName
        );

        $this->webAuthnManager
            ->method('generateRegistrationOptions')
            ->with(
                $userId,
                $userName,
                $userDisplayName,
                ['cred1', 'cred2'],
                false,
                false
            )
            ->willReturn($expectedOptions);

        $result = $this->service->generateWebAuthnRegistrationOptions(
            $userId,
            $userName,
            $userDisplayName
        );

        $this->assertInstanceOf(WebAuthnRegistrationOptions::class, $result);
    }

    #[Test]
    public function completes_webauthn_registration(): void
    {
        $userId = 'user123';
        $attestationJson = '{"id":"credId","response":{}}';
        $challenge = base64_encode('challenge');
        $origin = 'https://example.com';
        $friendlyName = 'My YubiKey';

        $credential = WebAuthnCredential::create(
            credentialId: 'credId',
            publicKey: base64_encode('publicKey'),
            signCount: 0,
            transports: ['usb'],
            aaguid: '00000000-0000-0000-0000-000000000000'
        );

        $this->webAuthnManager
            ->method('verifyRegistration')
            ->willReturn($credential);

        $this->enrollmentRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->credentialRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($userId, $friendlyName) {
                return $data['user_id'] === $userId
                    && $data['friendly_name'] === $friendlyName;
            }));

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($userId) {
                return $data['user_id'] === $userId
                    && $data['method'] === MfaMethod::PASSKEY->value
                    && $data['is_active'] === true;
            }));

        $result = $this->service->completeWebAuthnRegistration(
            $userId,
            $attestationJson,
            $challenge,
            $origin,
            $friendlyName
        );

        $this->assertInstanceOf(WebAuthnCredential::class, $result);
    }

    #[Test]
    public function generates_backup_codes(): void
    {
        $userId = 'user123';
        $count = 10;

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('revokeByUserAndMethod')
            ->with($userId, MfaMethod::BACKUP_CODES->value);

        $this->enrollmentRepository
            ->expects($this->exactly($count))
            ->method('create')
            ->with($this->callback(function ($data) use ($userId) {
                return $data['user_id'] === $userId
                    && $data['method'] === MfaMethod::BACKUP_CODES->value
                    && $data['is_active'] === true
                    && isset($data['secret']['hash']);
            }));

        $result = $this->service->generateBackupCodes($userId, $count);

        $this->assertInstanceOf(BackupCodeSet::class, $result);
        $this->assertCount($count, $result->codes);
    }

    #[Test]
    public function throws_exception_for_invalid_backup_code_count(): void
    {
        $userId = 'user123';

        $this->expectException(MfaEnrollmentException::class);
        $this->expectExceptionMessage('Backup code count 5 is invalid');

        $this->service->generateBackupCodes($userId, 5);
    }

    #[Test]
    public function revokes_enrollment_successfully(): void
    {
        $userId = 'user123';
        $enrollmentId = 'enrollment123';

        // Create mock enrollment
        $enrollment = $this->createMock(MfaEnrollmentDataInterface::class);
        $enrollment->method('getId')->willReturn($enrollmentId);
        $enrollment->method('getUserId')->willReturn($userId);
        $enrollment->method('getMethod')->willReturn(MfaMethod::TOTP);

        $this->enrollmentRepository
            ->method('findById')
            ->with($enrollmentId)
            ->willReturn($enrollment);

        $this->enrollmentRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['id' => $enrollmentId, 'is_active' => true],
                ['id' => 'enrollment456', 'is_active' => true],
            ]);

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('revoke')
            ->with($enrollmentId);

        $result = $this->service->revokeEnrollment($userId, $enrollmentId);

        $this->assertTrue($result);
    }

    #[Test]
    public function throws_exception_when_revoking_last_method(): void
    {
        $userId = 'user123';
        $enrollmentId = 'enrollment123';

        // Create mock enrollment
        $enrollment = $this->createMock(MfaEnrollmentDataInterface::class);
        $enrollment->method('getId')->willReturn($enrollmentId);
        $enrollment->method('getUserId')->willReturn($userId);

        $this->enrollmentRepository
            ->method('findById')
            ->with($enrollmentId)
            ->willReturn($enrollment);

        $this->enrollmentRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['id' => $enrollmentId, 'is_active' => true],
            ]);

        $this->expectException(MfaEnrollmentException::class);
        $this->expectExceptionMessage('Cannot revoke last authentication method');

        $this->service->revokeEnrollment($userId, $enrollmentId);
    }

    #[Test]
    public function revokes_webauthn_credential(): void
    {
        $userId = 'user123';
        $credentialId = 'cred123';

        $this->credentialRepository
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn([
                'credential_id' => $credentialId,
                'user_id' => $userId,
            ]);

        $this->credentialRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['credential_id' => $credentialId],
                ['credential_id' => 'cred456'],
            ]);

        $this->enrollmentRepository
            ->method('findByUserId')
            ->willReturn([
                ['method' => MfaMethod::PASSKEY->value, 'is_active' => true],
                ['method' => MfaMethod::TOTP->value, 'is_active' => true],
            ]);

        $this->credentialRepository
            ->expects($this->once())
            ->method('revoke')
            ->with($credentialId);

        $result = $this->service->revokeWebAuthnCredential($userId, $credentialId);

        $this->assertTrue($result);
    }

    #[Test]
    public function updates_webauthn_credential_name(): void
    {
        $userId = 'user123';
        $credentialId = 'cred123';
        $newName = 'Updated YubiKey';

        $this->credentialRepository
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn([
                'credential_id' => $credentialId,
                'user_id' => $userId,
            ]);

        $this->credentialRepository
            ->expects($this->once())
            ->method('updateFriendlyName')
            ->with($credentialId, $newName);

        $result = $this->service->updateWebAuthnCredentialName($userId, $credentialId, $newName);

        $this->assertTrue($result);
    }

    #[Test]
    public function throws_exception_for_invalid_friendly_name(): void
    {
        $userId = 'user123';
        $credentialId = 'cred123';
        $invalidName = ''; // Empty name

        $this->expectException(MfaEnrollmentException::class);
        $this->expectExceptionMessage('Friendly name');

        $this->service->updateWebAuthnCredentialName($userId, $credentialId, $invalidName);
    }

    #[Test]
    public function checks_if_user_has_enrolled_mfa(): void
    {
        $userId = 'user123';

        $this->enrollmentRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['method' => MfaMethod::TOTP->value, 'is_active' => true],
            ]);

        $result = $this->service->hasEnrolledMfa($userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function checks_if_user_has_specific_method_enrolled(): void
    {
        $userId = 'user123';

        $this->enrollmentRepository
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([
                ['method' => MfaMethod::TOTP->value, 'is_active' => true],
                ['method' => MfaMethod::PASSKEY->value, 'is_active' => true],
            ]);

        $this->assertTrue($this->service->hasMethodEnrolled($userId, MfaMethod::TOTP->value));
        $this->assertTrue($this->service->hasMethodEnrolled($userId, MfaMethod::PASSKEY->value));
        $this->assertFalse($this->service->hasMethodEnrolled($userId, MfaMethod::SMS->value));
    }

    #[Test]
    public function enables_passwordless_mode(): void
    {
        $userId = 'user123';

        $this->credentialRepository
            ->method('findResidentKeysByUserId')
            ->with($userId)
            ->willReturn([
                ['credential_id' => 'cred123'],
            ]);

        $result = $this->service->enablePasswordlessMode($userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function throws_exception_when_no_resident_keys_for_passwordless(): void
    {
        $userId = 'user123';

        $this->credentialRepository
            ->method('findResidentKeysByUserId')
            ->with($userId)
            ->willReturn([]);

        $this->expectException(MfaEnrollmentException::class);
        $this->expectExceptionMessage('no resident keys');

        $this->service->enablePasswordlessMode($userId);
    }

    #[Test]
    public function admin_resets_mfa(): void
    {
        $userId = 'user123';
        $adminUserId = 'admin456';
        $reason = 'User lost device';

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with($userId);

        $this->credentialRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with($userId);

        $token = $this->service->adminResetMfa($userId, $adminUserId, $reason);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }
}
