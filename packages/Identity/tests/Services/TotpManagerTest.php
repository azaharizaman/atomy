<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\Services;

use Nexus\Identity\Services\TotpManager;
use Nexus\Identity\ValueObjects\TotpSecret;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TotpManager::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('totp')]
class TotpManagerTest extends TestCase
{
    private TotpManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TotpManager();
    }

    #[Test]
    public function it_generates_totp_secret_with_defaults(): void
    {
        $secret = $this->manager->generateSecret();

        $this->assertInstanceOf(TotpSecret::class, $secret);
        $this->assertSame('sha1', $secret->algorithm);
        $this->assertSame(30, $secret->period);
        $this->assertSame(6, $secret->digits);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16,}$/', $secret->secret);
    }

    #[Test]
    public function it_generates_totp_secret_with_custom_parameters(): void
    {
        $secret = $this->manager->generateSecret(
            algorithm: 'sha256',
            period: 60,
            digits: 8
        );

        $this->assertSame('sha256', $secret->algorithm);
        $this->assertSame(60, $secret->period);
        $this->assertSame(8, $secret->digits);
    }

    #[Test]
    public function it_generates_different_secrets_each_time(): void
    {
        $secret1 = $this->manager->generateSecret();
        $secret2 = $this->manager->generateSecret();

        $this->assertNotSame($secret1->secret, $secret2->secret);
    }

    #[Test]
    public function it_generates_qr_code_as_base64(): void
    {
        $secret = $this->manager->generateSecret();
        $qrCode = $this->manager->generateQrCode(
            $secret,
            'Nexus ERP',
            'user@example.com'
        );

        // Base64-encoded PNG should be non-empty and valid base64
        $this->assertNotEmpty($qrCode);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $qrCode);
        
        // Decode and verify it's a PNG
        $decoded = base64_decode($qrCode, true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    #[Test]
    public function it_generates_qr_code_with_custom_size(): void
    {
        $secret = $this->manager->generateSecret();
        $qrCode = $this->manager->generateQrCode(
            $secret,
            'Nexus ERP',
            'user@example.com',
            size: 400
        );

        $decoded = base64_decode($qrCode);
        $image = imagecreatefromstring($decoded);
        
        // Size should be approximately 400x400 (with margin)
        $this->assertGreaterThan(390, imagesx($image));
        $this->assertLessThan(430, imagesx($image));
        
        imagedestroy($image);
    }

    #[Test]
    public function it_generates_qr_code_data_uri(): void
    {
        $secret = $this->manager->generateSecret();
        $dataUri = $this->manager->generateQrCodeDataUri(
            $secret,
            'Nexus ERP',
            'user@example.com'
        );

        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        
        // Extract and verify base64 part
        $base64 = substr($dataUri, 22);
        $decoded = base64_decode($base64, true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    #[Test]
    public function it_verifies_valid_totp_code(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        // Generate current code
        $code = $this->manager->getCurrentCode($secret);
        
        // Verify it
        $this->assertTrue($this->manager->verifyCode($secret, $code));
    }

    #[Test]
    public function it_rejects_invalid_totp_code(): void
    {
        $secret = $this->manager->generateSecret();
        
        $this->assertFalse($this->manager->verifyCode($secret, '000000'));
        $this->assertFalse($this->manager->verifyCode($secret, '999999'));
        $this->assertFalse($this->manager->verifyCode($secret, 'ABCDEF'));
    }

    #[Test]
    public function it_verifies_code_within_time_window(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $timestamp = 1640000010; // Multiple of 30 is 1640000010
        $code = $this->manager->getCurrentCode($secret, $timestamp);

        // Should verify at exact time
        $this->assertTrue($this->manager->verifyCode($secret, $code, window: 1, timestamp: $timestamp));

        // Should verify 5 seconds later (within same period, or within window=10 if it crossed boundary)
        $this->assertTrue($this->manager->verifyCode($secret, $code, window: 10, timestamp: $timestamp + 5));

        // Should verify 5 seconds earlier
        $this->assertTrue($this->manager->verifyCode($secret, $code, window: 10, timestamp: $timestamp - 5));
    }

    #[Test]
    public function it_rejects_code_outside_time_window(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $timestamp = 1640000010;
        $code = $this->manager->getCurrentCode($secret, $timestamp);

        // Should reject at T+35 with small window (1s) since it's in next period
        $this->assertFalse($this->manager->verifyCode($secret, $code, window: 1, timestamp: $timestamp + 35));
    }

    #[Test]
    public function it_supports_larger_time_window(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $timestamp = 1640000010; // Start of period
        $code = $this->manager->getCurrentCode($secret, $timestamp + 29); // End of period

        // At timestamp + 31, we are in NEXT period.
        // Distance is 2 seconds from timestamp + 29.
        // Window of 5 seconds should cover it.
        $this->assertTrue($this->manager->verifyCode($secret, $code, window: 5, timestamp: $timestamp + 31));
    }

    #[Test]
    public function it_generates_current_code(): void
    {
        $secret = $this->manager->generateSecret();
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verifyCode($secret, $code));
    }

    #[Test]
    public function it_generates_current_code_for_8_digits(): void
    {
        $secret = $this->manager->generateSecret(digits: 8);
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
        $this->assertTrue($this->manager->verifyCode($secret, $code));
    }

    #[Test]
    public function it_generates_deterministic_code_for_timestamp(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $timestamp = 1640000000;
        
        // Same timestamp should produce same code
        $code1 = $this->manager->getCurrentCode($secret, $timestamp);
        $code2 = $this->manager->getCurrentCode($secret, $timestamp);
        
        $this->assertSame($code1, $code2);
    }

    #[Test]
    public function it_calculates_remaining_seconds(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        // 1640000010 is a multiple of 30.
        // At timestamp 1640000015 (5 seconds into period)
        // Should have 25 seconds remaining
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000015);
        
        $this->assertSame(25, $remaining);
    }

    #[Test]
    public function it_calculates_remaining_seconds_at_period_start(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        // At exact period start (multiple of 30)
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000010);
        
        $this->assertSame(30, $remaining);
    }

    #[Test]
    public function it_calculates_remaining_seconds_at_period_end(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        // At 1 second before period end (multiple of 30 - 1)
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000039);
        
        $this->assertSame(1, $remaining);
    }

    #[Test]
    public function it_generates_provisioning_uri(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $uri = $this->manager->getProvisioningUri($secret, 'Nexus ERP', 'user@example.com');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('Nexus%20ERP', $uri);
        $this->assertStringContainsString('user%40example.com', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
    }

    #[Test]
    public function it_supports_sha256_algorithm(): void
    {
        $secret = $this->manager->generateSecret(algorithm: 'sha256');
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verifyCode($secret, $code));
    }

    #[Test]
    public function it_supports_sha512_algorithm(): void
    {
        $secret = $this->manager->generateSecret(algorithm: 'sha512');
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verifyCode($secret, $code));
    }

    #[Test]
    public function it_supports_custom_period(): void
    {
        $secret = $this->manager->generateSecret(period: 60);
        $code = $this->manager->getCurrentCode($secret);

        // Code should remain valid for full 60-second period
        $this->assertTrue($this->manager->verifyCode($secret, $code, window: 0));
    }
}
