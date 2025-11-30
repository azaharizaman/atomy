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
        $this->assertSame('sha1', $secret->getAlgorithm());
        $this->assertSame(30, $secret->getPeriod());
        $this->assertSame(6, $secret->getDigits());
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret->getSecret());
    }

    #[Test]
    public function it_generates_totp_secret_with_custom_parameters(): void
    {
        $secret = $this->manager->generateSecret(
            algorithm: 'sha256',
            period: 60,
            digits: 8
        );

        $this->assertSame('sha256', $secret->getAlgorithm());
        $this->assertSame(60, $secret->getPeriod());
        $this->assertSame(8, $secret->getDigits());
    }

    #[Test]
    public function it_generates_different_secrets_each_time(): void
    {
        $secret1 = $this->manager->generateSecret();
        $secret2 = $this->manager->generateSecret();

        $this->assertNotSame($secret1->getSecret(), $secret2->getSecret());
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
        $this->assertTrue($this->manager->verify($secret, $code));
    }

    #[Test]
    public function it_rejects_invalid_totp_code(): void
    {
        $secret = $this->manager->generateSecret();
        
        $this->assertFalse($this->manager->verify($secret, '000000'));
        $this->assertFalse($this->manager->verify($secret, '999999'));
        $this->assertFalse($this->manager->verify($secret, 'ABCDEF'));
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

        $timestamp = 1640000000; // Fixed timestamp
        $code = $this->manager->getCurrentCode($secret, $timestamp);
        
        // Should verify at exact time
        $this->assertTrue($this->manager->verify($secret, $code, window: 1, timestamp: $timestamp));
        
        // Should verify 30 seconds later (1 period, within window=1)
        $this->assertTrue($this->manager->verify($secret, $code, window: 1, timestamp: $timestamp + 30));
        
        // Should verify 30 seconds earlier (1 period, within window=1)
        $this->assertTrue($this->manager->verify($secret, $code, window: 1, timestamp: $timestamp - 30));
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

        $timestamp = 1640000000;
        $code = $this->manager->getCurrentCode($secret, $timestamp);
        
        // Should reject 2 periods later (outside window=1)
        $this->assertFalse($this->manager->verify($secret, $code, window: 1, timestamp: $timestamp + 60));
        
        // Should reject 2 periods earlier (outside window=1)
        $this->assertFalse($this->manager->verify($secret, $code, window: 1, timestamp: $timestamp - 60));
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

        $timestamp = 1640000000;
        $code = $this->manager->getCurrentCode($secret, $timestamp);
        
        // With window=2, should accept up to 2 periods away
        $this->assertTrue($this->manager->verify($secret, $code, window: 2, timestamp: $timestamp + 60));
        $this->assertTrue($this->manager->verify($secret, $code, window: 2, timestamp: $timestamp - 60));
    }

    #[Test]
    public function it_generates_current_code(): void
    {
        $secret = $this->manager->generateSecret();
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verify($secret, $code));
    }

    #[Test]
    public function it_generates_current_code_for_8_digits(): void
    {
        $secret = $this->manager->generateSecret(digits: 8);
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
        $this->assertTrue($this->manager->verify($secret, $code));
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

        // At timestamp 1640000005 (5 seconds into period)
        // Should have 25 seconds remaining
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000005);
        
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

        // At exact period start, should have full 30 seconds
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000000);
        
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

        // At 1 second before period end
        $remaining = $this->manager->getRemainingSeconds($secret, 1640000029);
        
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
        $this->assertStringContainsString('user@example.com', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
    }

    #[Test]
    public function it_supports_sha256_algorithm(): void
    {
        $secret = $this->manager->generateSecret(algorithm: 'sha256');
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verify($secret, $code));
    }

    #[Test]
    public function it_supports_sha512_algorithm(): void
    {
        $secret = $this->manager->generateSecret(algorithm: 'sha512');
        $code = $this->manager->getCurrentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->manager->verify($secret, $code));
    }

    #[Test]
    public function it_supports_custom_period(): void
    {
        $secret = $this->manager->generateSecret(period: 60);
        $code = $this->manager->getCurrentCode($secret);

        // Code should remain valid for full 60-second period
        $this->assertTrue($this->manager->verify($secret, $code, window: 0));
    }
}
