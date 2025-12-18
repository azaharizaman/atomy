<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Enums;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsymmetricAlgorithm::class)]
final class AsymmetricAlgorithmTest extends TestCase
{
    #[Test]
    public function all_cases_have_valid_string_values(): void
    {
        $this->assertSame('hmac-sha256', AsymmetricAlgorithm::HMACSHA256->value);
        $this->assertSame('ed25519', AsymmetricAlgorithm::ED25519->value);
        $this->assertSame('rsa-2048', AsymmetricAlgorithm::RSA2048->value);
        $this->assertSame('rsa-4096', AsymmetricAlgorithm::RSA4096->value);
        $this->assertSame('ecdsa-p256', AsymmetricAlgorithm::ECDSAP256->value);
        $this->assertSame('dilithium3', AsymmetricAlgorithm::DILITHIUM3->value);
        $this->assertSame('kyber768', AsymmetricAlgorithm::KYBER768->value);
    }

    #[Test]
    #[DataProvider('quantumResistanceProvider')]
    public function isQuantumResistant_returns_correct_value(AsymmetricAlgorithm $algorithm, bool $expected): void
    {
        $this->assertSame($expected, $algorithm->isQuantumResistant());
    }

    public static function quantumResistanceProvider(): array
    {
        return [
            'HMACSHA256 not quantum resistant' => [AsymmetricAlgorithm::HMACSHA256, false],
            'ED25519 not quantum resistant' => [AsymmetricAlgorithm::ED25519, false],
            'RSA2048 not quantum resistant' => [AsymmetricAlgorithm::RSA2048, false],
            'RSA4096 not quantum resistant' => [AsymmetricAlgorithm::RSA4096, false],
            'ECDSAP256 not quantum resistant' => [AsymmetricAlgorithm::ECDSAP256, false],
            'DILITHIUM3 is quantum resistant' => [AsymmetricAlgorithm::DILITHIUM3, true],
            'KYBER768 is quantum resistant' => [AsymmetricAlgorithm::KYBER768, true],
        ];
    }

    #[Test]
    #[DataProvider('securityLevelProvider')]
    public function getSecurityLevel_returns_correct_value(AsymmetricAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getSecurityLevel());
    }

    public static function securityLevelProvider(): array
    {
        return [
            'HMACSHA256' => [AsymmetricAlgorithm::HMACSHA256, 256],
            'ED25519' => [AsymmetricAlgorithm::ED25519, 128],
            'RSA2048' => [AsymmetricAlgorithm::RSA2048, 112],
            'RSA4096' => [AsymmetricAlgorithm::RSA4096, 128],
            'ECDSAP256' => [AsymmetricAlgorithm::ECDSAP256, 128],
            'DILITHIUM3' => [AsymmetricAlgorithm::DILITHIUM3, 192],
            'KYBER768' => [AsymmetricAlgorithm::KYBER768, 192],
        ];
    }

    #[Test]
    #[DataProvider('typeProvider')]
    public function getType_returns_correct_value(AsymmetricAlgorithm $algorithm, string $expected): void
    {
        $this->assertSame($expected, $algorithm->getType());
    }

    public static function typeProvider(): array
    {
        return [
            'HMACSHA256 is mac' => [AsymmetricAlgorithm::HMACSHA256, 'mac'],
            'ED25519 is signature' => [AsymmetricAlgorithm::ED25519, 'signature'],
            'RSA2048 is signature' => [AsymmetricAlgorithm::RSA2048, 'signature'],
            'RSA4096 is signature' => [AsymmetricAlgorithm::RSA4096, 'signature'],
            'ECDSAP256 is signature' => [AsymmetricAlgorithm::ECDSAP256, 'signature'],
            'DILITHIUM3 is signature' => [AsymmetricAlgorithm::DILITHIUM3, 'signature'],
            'KYBER768 is kem' => [AsymmetricAlgorithm::KYBER768, 'kem'],
        ];
    }

    #[Test]
    #[DataProvider('implementedProvider')]
    public function isImplemented_returns_correct_value(AsymmetricAlgorithm $algorithm, bool $expected): void
    {
        $this->assertSame($expected, $algorithm->isImplemented());
    }

    public static function implementedProvider(): array
    {
        return [
            'HMACSHA256 implemented' => [AsymmetricAlgorithm::HMACSHA256, true],
            'ED25519 implemented' => [AsymmetricAlgorithm::ED25519, true],
            'RSA2048 implemented' => [AsymmetricAlgorithm::RSA2048, true],
            'RSA4096 implemented' => [AsymmetricAlgorithm::RSA4096, true],
            'ECDSAP256 implemented' => [AsymmetricAlgorithm::ECDSAP256, true],
            'DILITHIUM3 not yet implemented' => [AsymmetricAlgorithm::DILITHIUM3, false],
            'KYBER768 not yet implemented' => [AsymmetricAlgorithm::KYBER768, false],
        ];
    }

    #[Test]
    #[DataProvider('publicKeyLengthProvider')]
    public function getPublicKeyLength_returns_correct_value(AsymmetricAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getPublicKeyLength());
    }

    public static function publicKeyLengthProvider(): array
    {
        return [
            'HMACSHA256 no public key' => [AsymmetricAlgorithm::HMACSHA256, 0],
            'ED25519' => [AsymmetricAlgorithm::ED25519, 32],
            'RSA2048' => [AsymmetricAlgorithm::RSA2048, 294],
            'RSA4096' => [AsymmetricAlgorithm::RSA4096, 550],
            'ECDSAP256' => [AsymmetricAlgorithm::ECDSAP256, 65],
            'DILITHIUM3' => [AsymmetricAlgorithm::DILITHIUM3, 1952],
            'KYBER768' => [AsymmetricAlgorithm::KYBER768, 1184],
        ];
    }

    #[Test]
    #[DataProvider('signatureLengthProvider')]
    public function getSignatureLength_returns_correct_value(AsymmetricAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getSignatureLength());
    }

    public static function signatureLengthProvider(): array
    {
        return [
            'HMACSHA256' => [AsymmetricAlgorithm::HMACSHA256, 32],
            'ED25519' => [AsymmetricAlgorithm::ED25519, 64],
            'RSA2048' => [AsymmetricAlgorithm::RSA2048, 256],
            'RSA4096' => [AsymmetricAlgorithm::RSA4096, 512],
            'ECDSAP256' => [AsymmetricAlgorithm::ECDSAP256, 64],
            'DILITHIUM3' => [AsymmetricAlgorithm::DILITHIUM3, 3293],
            'KYBER768 no signature' => [AsymmetricAlgorithm::KYBER768, 0],
        ];
    }
}
