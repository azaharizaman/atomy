<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MfaEnrollment;
use App\Repository\MfaEnrollmentRepository;
use Nexus\Identity\Contracts\MfaVerifierInterface;
use Symfony\Component\Uid\Ulid;

final readonly class MfaVerifier implements MfaVerifierInterface
{
    private const TOTP_WINDOW = 1; // Allow 1 step before/after current time

    public function __construct(
        private MfaEnrollmentRepository $mfaRepository,
    ) {}

    public function verifyTotp(string $userId, string $code): bool
    {
        $enrollment = $this->mfaRepository->findByUserIdAndMethod($userId, 'totp');

        if ($enrollment === null || !$enrollment->isVerified()) {
            return false;
        }

        $secret = $enrollment->getSecret();
        if ($secret === null) {
            return false;
        }

        return $this->verifyTotpCode($secret, $code);
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        $enrollment = $this->mfaRepository->findByUserIdAndMethod($userId, 'backup_codes');

        if ($enrollment === null || !$enrollment->isVerified()) {
            return false;
        }

        // In a real implementation, backup codes would be stored hashed
        // and removed after use. This is a simplified version.
        $secret = $enrollment->getSecret();
        if ($secret === null) {
            return false;
        }

        // Check if code matches (codes stored as JSON array in secret)
        $codes = json_decode($secret, true) ?? [];
        $codeHash = hash('sha256', $code);

        $index = array_search($codeHash, $codes, true);
        if ($index === false) {
            return false;
        }

        // Remove used code
        unset($codes[$index]);
        $this->updateBackupCodes($enrollment, array_values($codes));

        return true;
    }

    public function requiresMfa(string $userId): bool
    {
        return $this->mfaRepository->hasVerifiedEnrollment($userId);
    }

    public function trustDevice(string $userId, string $deviceFingerprint, int $ttlDays = 30): string
    {
        $em = $this->mfaRepository->getEntityManager();

        // Check if device is already trusted
        $existing = $this->findTrustedDevice($userId, $deviceFingerprint);
        if ($existing !== null) {
            // Remove old trust
            $em->remove($existing);
        }

        $trustId = (string) new Ulid();
        $enrollment = new MfaEnrollment(
            id: $trustId,
            userId: $userId,
            method: 'trusted_device',
            secret: json_encode([
                'fingerprint' => $deviceFingerprint,
                'expires_at' => (new \DateTimeImmutable("+{$ttlDays} days"))->format('c'),
            ]),
        );
        $enrollment->verify();

        $em->persist($enrollment);
        $em->flush();

        return $trustId;
    }

    public function isDeviceTrusted(string $userId, string $deviceFingerprint): bool
    {
        $trust = $this->findTrustedDevice($userId, $deviceFingerprint);
        if ($trust === null) {
            return false;
        }

        $data = json_decode($trust->getSecret() ?? '{}', true);
        $expiresAt = $data['expires_at'] ?? null;

        if ($expiresAt === null) {
            return false;
        }

        return new \DateTimeImmutable($expiresAt) > new \DateTimeImmutable();
    }

    public function revokeTrustedDevice(string $userId, string $deviceId): void
    {
        $enrollment = $this->mfaRepository->find($deviceId);
        if ($enrollment !== null && 
            $enrollment->getUserId() === $userId && 
            $enrollment->getMethod() === 'trusted_device'
        ) {
            $em = $this->mfaRepository->getEntityManager();
            $em->remove($enrollment);
            $em->flush();
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getTrustedDevices(string $userId): array
    {
        $enrollments = $this->mfaRepository->findByUserId($userId);
        $devices = [];

        foreach ($enrollments as $enrollment) {
            if ($enrollment->getMethod() !== 'trusted_device') {
                continue;
            }

            $data = json_decode($enrollment->getSecret() ?? '{}', true);
            $devices[] = [
                'id' => $enrollment->getId(),
                'fingerprint' => $data['fingerprint'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'created_at' => $enrollment->getCreatedAt()->format('c'),
            ];
        }

        return $devices;
    }

    /**
     * Enroll user in TOTP MFA
     *
     * @return array{secret: string, qr_uri: string}
     */
    public function enrollTotp(string $userId, string $issuer = 'Nexus'): array
    {
        // Generate a random secret (base32 encoded)
        $secret = $this->generateTotpSecret();

        $enrollment = new MfaEnrollment(
            id: (string) new Ulid(),
            userId: $userId,
            method: 'totp',
            secret: $secret,
        );

        $em = $this->mfaRepository->getEntityManager();
        $em->persist($enrollment);
        $em->flush();

        // Generate QR URI for authenticator apps
        $qrUri = sprintf(
            'otpauth://totp/%s:user@example.com?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            urlencode($issuer),
            $secret,
            urlencode($issuer)
        );

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
        ];
    }

    /**
     * Verify TOTP enrollment with initial code
     */
    public function verifyTotpEnrollment(string $userId, string $code): bool
    {
        $enrollment = $this->mfaRepository->findByUserIdAndMethod($userId, 'totp');

        if ($enrollment === null || $enrollment->isVerified()) {
            return false;
        }

        if (!$this->verifyTotpCode($enrollment->getSecret() ?? '', $code)) {
            return false;
        }

        $enrollment->verify();
        $enrollment->setPrimary(true);
        $this->mfaRepository->getEntityManager()->flush();

        return true;
    }

    /**
     * Generate backup codes for user
     *
     * @return string[]
     */
    public function generateBackupCodes(string $userId, int $count = 10): array
    {
        $codes = [];
        $hashedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $codes[] = $code;
            $hashedCodes[] = hash('sha256', $code);
        }

        // Remove existing backup codes
        $existing = $this->mfaRepository->findByUserIdAndMethod($userId, 'backup_codes');
        $em = $this->mfaRepository->getEntityManager();

        if ($existing !== null) {
            $em->remove($existing);
        }

        $enrollment = new MfaEnrollment(
            id: (string) new Ulid(),
            userId: $userId,
            method: 'backup_codes',
            secret: json_encode($hashedCodes),
        );
        $enrollment->verify();

        $em->persist($enrollment);
        $em->flush();

        return $codes;
    }

    private function verifyTotpCode(string $secret, string $code): bool
    {
        $timestamp = time();
        $period = 30;

        for ($offset = -self::TOTP_WINDOW; $offset <= self::TOTP_WINDOW; $offset++) {
            $counter = (int) floor(($timestamp + ($offset * $period)) / $period);
            $expectedCode = $this->generateTotpCode($secret, $counter);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateTotpCode(string $secret, int $counter): string
    {
        // Pack counter as 64-bit big-endian
        $counterBytes = pack('N*', 0, $counter);

        // Decode base32 secret
        $key = $this->base32Decode($secret);

        // Generate HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0f;
        $binary = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );

        $otp = $binary % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function generateTotpSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    private function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $input = str_replace('=', '', $input);

        $buffer = 0;
        $bufferLen = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos($alphabet, $input[$i]);
            if ($val === false) continue;

            $buffer = ($buffer << 5) | $val;
            $bufferLen += 5;

            if ($bufferLen >= 8) {
                $bufferLen -= 8;
                $output .= chr(($buffer >> $bufferLen) & 0xff);
            }
        }

        return $output;
    }

    private function findTrustedDevice(string $userId, string $fingerprint): ?MfaEnrollment
    {
        $enrollments = $this->mfaRepository->findByUserId($userId);

        foreach ($enrollments as $enrollment) {
            if ($enrollment->getMethod() !== 'trusted_device') {
                continue;
            }

            $data = json_decode($enrollment->getSecret() ?? '{}', true);
            if (($data['fingerprint'] ?? null) === $fingerprint) {
                return $enrollment;
            }
        }

        return null;
    }

    /**
     * @param string[] $codes
     */
    private function updateBackupCodes(MfaEnrollment $enrollment, array $codes): void
    {
        // We need to use reflection to update the secret since it's private
        $reflection = new \ReflectionClass($enrollment);
        $prop = $reflection->getProperty('secret');
        $prop->setValue($enrollment, json_encode($codes));

        $this->mfaRepository->getEntityManager()->flush();
    }

    /**
     * Get user's MFA enrollments
     *
     * @return array<array<string, mixed>>
     */
    public function getEnrollments(string $userId): array
    {
        $enrollments = $this->mfaRepository->findByUserId($userId);
        $result = [];

        foreach ($enrollments as $enrollment) {
            if ($enrollment->getMethod() === 'trusted_device') {
                continue; // Separate endpoint for trusted devices
            }

            $result[] = [
                'id' => $enrollment->getId(),
                'method' => $enrollment->getMethod(),
                'verified' => $enrollment->isVerified(),
                'primary' => $enrollment->isPrimary(),
                'created_at' => $enrollment->getCreatedAt()->format('c'),
            ];
        }

        return $result;
    }
}
