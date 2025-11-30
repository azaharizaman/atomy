<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Domain\Identity\ValueObjects\DeviceFingerprint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DeviceFingerprint::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class DeviceFingerprintTest extends TestCase
{
    private const VALID_HASH = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    #[Test]
    public function it_creates_valid_device_fingerprint(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 10:30:00');
        
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            createdAt: $createdAt
        );

        $this->assertSame(self::VALID_HASH, $fingerprint->hash);
        $this->assertSame('web', $fingerprint->platform);
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0', $fingerprint->userAgent);
        $this->assertSame($createdAt, $fingerprint->createdAt);
    }

    #[Test]
    public function it_rejects_empty_hash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device fingerprint hash cannot be empty');

        new DeviceFingerprint(
            hash: '',
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );
    }

    #[Test]
    #[DataProvider('invalidHashProvider')]
    public function it_rejects_invalid_hash_format(string $invalidHash): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device fingerprint hash must be a valid SHA256 hex string');

        new DeviceFingerprint(
            hash: $invalidHash,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );
    }

    public static function invalidHashProvider(): array
    {
        return [
            'Too short' => ['a1b2c3d4'],
            'Too long' => [str_repeat('a', 65)],
            'Invalid characters' => ['g1h2i3j4k5l6m7n8o9p0q1r2s3t4u5v6w7x8y9z0a1b2c3d4e5f6g7h8i9j0k1l2'],
            'Uppercase and invalid length' => ['A1B2C3D4E5F6'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPlatformProvider')]
    public function it_rejects_invalid_platform(string $invalidPlatform): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid platform');

        new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: $invalidPlatform,
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );
    }

    public static function invalidPlatformProvider(): array
    {
        return [
            'Invalid name' => ['windows'],
            'Empty string' => [''],
            'Random value' => ['foobar'],
        ];
    }

    #[Test]
    #[DataProvider('validPlatformProvider')]
    public function it_accepts_valid_platforms(string $validPlatform): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: $validPlatform,
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );

        $this->assertSame($validPlatform, $fingerprint->platform);
    }

    public static function validPlatformProvider(): array
    {
        return [
            'Web' => ['web'],
            'iOS' => ['ios'],
            'Android' => ['android'],
            'Desktop' => ['desktop'],
            'Unknown' => ['unknown'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUserAgentProvider')]
    public function it_rejects_invalid_user_agent(string $invalidUserAgent): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: $invalidUserAgent,
            createdAt: new DateTimeImmutable()
        );
    }

    public static function invalidUserAgentProvider(): array
    {
        return [
            'Empty string' => [''],
            'Whitespace only' => ['   '],
            'Too long' => [str_repeat('a', 501)],
        ];
    }

    #[Test]
    public function it_matches_identical_hash_using_timing_safe_comparison(): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($fingerprint->matches(self::VALID_HASH));
    }

    #[Test]
    public function it_does_not_match_different_hash(): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );

        $differentHash = 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3';
        $this->assertFalse($fingerprint->matches($differentHash));
    }

    #[Test]
    public function it_detects_expired_fingerprint(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $now = new DateTimeImmutable('2024-02-01 00:00:00');  // 31 days later
        
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: $createdAt
        );

        $ttl30Days = 30 * 24 * 60 * 60;  // 30 days in seconds
        $this->assertTrue($fingerprint->isExpired($ttl30Days, $now));
    }

    #[Test]
    public function it_detects_non_expired_fingerprint(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 00:00:00');
        $now = new DateTimeImmutable('2024-01-20 00:00:00');  // 5 days later
        
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: $createdAt
        );

        $ttl30Days = 30 * 24 * 60 * 60;
        $this->assertFalse($fingerprint->isExpired($ttl30Days, $now));
    }

    #[Test]
    public function it_calculates_age_in_seconds(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $now = new DateTimeImmutable('2024-01-15 10:05:00');  // 5 minutes later
        
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: $createdAt
        );

        $this->assertSame(300, $fingerprint->getAgeInSeconds($now));  // 5 * 60 = 300 seconds
    }

    #[Test]
    public function it_identifies_web_platform(): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($fingerprint->isWebPlatform());
        $this->assertFalse($fingerprint->isMobilePlatform());
    }

    #[Test]
    public function it_identifies_mobile_platforms(): void
    {
        $iosFingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'ios',
            userAgent: 'iOS App',
            createdAt: new DateTimeImmutable()
        );

        $androidFingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'android',
            userAgent: 'Android App',
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($iosFingerprint->isMobilePlatform());
        $this->assertTrue($androidFingerprint->isMobilePlatform());
        $this->assertFalse($iosFingerprint->isWebPlatform());
    }

    #[Test]
    #[DataProvider('platformDisplayNameProvider')]
    public function it_returns_platform_display_name(string $platform, string $expectedName): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: $platform,
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );

        $this->assertSame($expectedName, $fingerprint->getPlatformDisplayName());
    }

    public static function platformDisplayNameProvider(): array
    {
        return [
            'Web' => ['web', 'Web Browser'],
            'iOS' => ['ios', 'iOS'],
            'Android' => ['android', 'Android'],
            'Desktop' => ['desktop', 'Desktop App'],
            'Unknown' => ['unknown', 'Unknown Device'],
        ];
    }

    #[Test]
    #[DataProvider('browserDetectionProvider')]
    public function it_detects_browser_from_user_agent(string $userAgent, string $expectedBrowser): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: $userAgent,
            createdAt: new DateTimeImmutable()
        );

        $this->assertSame($expectedBrowser, $fingerprint->getBrowserName());
    }

    public static function browserDetectionProvider(): array
    {
        return [
            'Chrome' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Google Chrome',
            ],
            'Firefox' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
                'Mozilla Firefox',
            ],
            'Safari' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
                'Safari',
            ],
            'Edge' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
                'Microsoft Edge',
            ],
            'Opera' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0',
                'Opera',
            ],
            'Unknown' => [
                'CustomBrowser/1.0',
                'Unknown Browser',
            ],
        ];
    }

    #[Test]
    public function it_returns_na_for_browser_name_on_non_web_platform(): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'ios',
            userAgent: 'iOS App',
            createdAt: new DateTimeImmutable()
        );

        $this->assertSame('N/A', $fingerprint->getBrowserName());
    }

    #[Test]
    public function it_generates_consistent_hash_from_device_characteristics(): void
    {
        $secret = 'test-secret-key';
        $platform = 'web';
        $userAgent = 'Mozilla/5.0';
        $ipAddress = '192.168.1.1';

        $hash1 = DeviceFingerprint::generateHash($secret, $platform, $userAgent, $ipAddress);
        $hash2 = DeviceFingerprint::generateHash($secret, $platform, $userAgent, $ipAddress);

        $this->assertSame($hash1, $hash2);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash1);
    }

    #[Test]
    public function it_generates_different_hash_for_different_characteristics(): void
    {
        $secret = 'test-secret-key';

        $hash1 = DeviceFingerprint::generateHash($secret, 'web', 'Mozilla/5.0', '192.168.1.1');
        $hash2 = DeviceFingerprint::generateHash($secret, 'web', 'Mozilla/5.0', '192.168.1.2');

        $this->assertNotSame($hash1, $hash2);
    }

    #[Test]
    public function it_generates_hash_without_ip_address(): void
    {
        $secret = 'test-secret-key';
        $platform = 'web';
        $userAgent = 'Mozilla/5.0';

        $hash = DeviceFingerprint::generateHash($secret, $platform, $userAgent);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 10:30:00');
        
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0 Chrome/120.0.0.0',
            createdAt: $createdAt
        );

        $array = $fingerprint->toArray();

        $this->assertSame([
            'hash' => self::VALID_HASH,
            'platform' => 'web',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'created_at' => '2024-01-15 10:30:00',
        ], $array);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $fingerprint = new DeviceFingerprint(
            hash: self::VALID_HASH,
            platform: 'web',
            userAgent: 'Mozilla/5.0',
            createdAt: new DateTimeImmutable()
        );
        
        $reflection = new ReflectionClass($fingerprint);
        
        // Verify all properties are readonly
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }
}
