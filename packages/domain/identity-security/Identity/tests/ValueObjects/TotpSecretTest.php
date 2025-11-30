<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use InvalidArgumentException;
use Nexus\Identity\ValueObjects\TotpSecret;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(TotpSecret::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class TotpSecretTest extends TestCase
{
    #[Test]
    public function it_creates_valid_totp_secret_with_defaults(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP'  // Valid Base32
        );

        $this->assertSame('JBSWY3DPEHPK3PXP', $secret->secret);
        $this->assertSame('sha1', $secret->algorithm);
        $this->assertSame(30, $secret->period);
        $this->assertSame(6, $secret->digits);
    }

    #[Test]
    public function it_creates_valid_totp_secret_with_custom_config(): void
    {
        $secret = new TotpSecret(
            secret: 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            algorithm: 'sha256',
            period: 60,
            digits: 8
        );

        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret->secret);
        $this->assertSame('sha256', $secret->algorithm);
        $this->assertSame(60, $secret->period);
        $this->assertSame(8, $secret->digits);
    }

    #[Test]
    public function it_rejects_secret_shorter_than_16_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TOTP secret must be at least 16 characters long');

        new TotpSecret(secret: 'SHORT');
    }

    #[Test]
    #[DataProvider('invalidBase32Provider')]
    public function it_rejects_invalid_base32_secret(string $invalidSecret): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TOTP secret must be Base32 encoded');

        new TotpSecret(secret: $invalidSecret);
    }

    public static function invalidBase32Provider(): array
    {
        return [
            'Lowercase letters' => ['jbswy3dpehpk3pxp'],  // Base32 must be uppercase
            'Contains 0' => ['JBSWY3DPEHPK3PXP0000'],  // 0 not in Base32
            'Contains 1' => ['JBSWY3DPEHPK3PXP1111'],  // 1 not in Base32
            'Contains 8' => ['JBSWY3DPEHPK3PXP8888'],  // 8 not in Base32
            'Contains 9' => ['JBSWY3DPEHPK3PXP9999'],  // 9 not in Base32
            'Contains special chars' => ['JBSWY3DP=HPK3PXP'],  // = not in Base32 alphabet (only padding)
            'Contains lowercase and numbers' => ['jbswy3dpehpk3pxp0123'],
        ];
    }

    #[Test]
    #[DataProvider('invalidAlgorithmProvider')]
    public function it_rejects_invalid_algorithm(string $invalidAlgorithm): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid algorithm');

        new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: $invalidAlgorithm
        );
    }

    public static function invalidAlgorithmProvider(): array
    {
        return [
            'md5' => ['md5'],
            'sha224' => ['sha224'],
            'sha384' => ['sha384'],
            'uppercase SHA1' => ['SHA1'],  // Must be lowercase
            'empty string' => [''],
        ];
    }

    #[Test]
    #[DataProvider('validAlgorithmProvider')]
    public function it_accepts_valid_algorithms(string $validAlgorithm): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: $validAlgorithm
        );

        $this->assertSame($validAlgorithm, $secret->algorithm);
    }

    public static function validAlgorithmProvider(): array
    {
        return [
            'sha1' => ['sha1'],
            'sha256' => ['sha256'],
            'sha512' => ['sha512'],
        ];
    }

    #[Test]
    public function it_rejects_invalid_period(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TOTP period must be at least 1 second');

        new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            period: 0
        );
    }

    #[Test]
    public function it_accepts_non_standard_but_valid_periods(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            period: 15  // Non-standard but valid
        );

        $this->assertSame(15, $secret->period);
    }

    #[Test]
    #[DataProvider('invalidDigitsProvider')]
    public function it_rejects_invalid_digits(int $invalidDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TOTP digits must be between 6 and 8');

        new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            digits: $invalidDigits
        );
    }

    public static function invalidDigitsProvider(): array
    {
        return [
            'Too few: 5' => [5],
            'Too few: 0' => [0],
            'Too many: 9' => [9],
            'Too many: 10' => [10],
        ];
    }

    #[Test]
    #[DataProvider('validDigitsProvider')]
    public function it_accepts_valid_digits(int $validDigits): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            digits: $validDigits
        );

        $this->assertSame($validDigits, $secret->digits);
    }

    public static function validDigitsProvider(): array
    {
        return [
            '6 digits (standard)' => [6],
            '7 digits' => [7],
            '8 digits' => [8],
        ];
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $secret = new TotpSecret(secret: 'JBSWY3DPEHPK3PXP');
        
        $reflection = new ReflectionClass($secret);
        
        // Verify all properties are readonly
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    #[Test]
    public function it_generates_correct_otpauth_uri(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $uri = $secret->getUri('Atomy ERP', 'user@example.com');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('Atomy%20ERP:user%40example.com', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=Atomy%20ERP', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);  // Uppercase in URI
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    #[Test]
    public function it_generates_uri_with_special_characters_encoded(): void
    {
        $secret = new TotpSecret(secret: 'JBSWY3DPEHPK3PXP');

        $uri = $secret->getUri('Atomy & Co.', 'user+test@example.com');

        // Special characters should be URL-encoded
        $this->assertStringContainsString('Atomy%20%26%20Co.', $uri);
        $this->assertStringContainsString('user%2Btest%40example.com', $uri);
    }

    #[Test]
    public function it_identifies_default_configuration(): void
    {
        $default = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha1',
            period: 30,
            digits: 6
        );

        $this->assertTrue($default->isDefault());
    }

    #[Test]
    public function it_identifies_non_default_configuration(): void
    {
        $nonDefault = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha256',  // Non-default
            period: 30,
            digits: 6
        );

        $this->assertFalse($nonDefault->isDefault());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $secret = new TotpSecret(
            secret: 'JBSWY3DPEHPK3PXP',
            algorithm: 'sha256',
            period: 60,
            digits: 8
        );

        $array = $secret->toArray();

        $this->assertSame([
            'secret' => 'JBSWY3DPEHPK3PXP',
            'algorithm' => 'sha256',
            'period' => 60,
            'digits' => 8,
        ], $array);
    }

    #[Test]
    public function it_handles_long_base32_secrets(): void
    {
        // 32-character secret (recommended length)
        $longSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        
        $secret = new TotpSecret(secret: $longSecret);

        $this->assertSame($longSecret, $secret->secret);
    }
}
