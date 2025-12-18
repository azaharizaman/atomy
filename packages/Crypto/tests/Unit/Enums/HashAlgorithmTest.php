<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Enums;

use Nexus\Crypto\Enums\HashAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HashAlgorithm::class)]
final class HashAlgorithmTest extends TestCase
{
    #[Test]
    public function all_cases_have_valid_string_values(): void
    {
        $this->assertSame('sha256', HashAlgorithm::SHA256->value);
        $this->assertSame('sha384', HashAlgorithm::SHA384->value);
        $this->assertSame('sha512', HashAlgorithm::SHA512->value);
        $this->assertSame('blake2b', HashAlgorithm::BLAKE2B->value);
    }

    #[Test]
    public function all_algorithms_are_quantum_resistant(): void
    {
        foreach (HashAlgorithm::cases() as $algorithm) {
            $this->assertTrue(
                $algorithm->isQuantumResistant(),
                "{$algorithm->name} should be quantum resistant"
            );
        }
    }

    #[Test]
    #[DataProvider('securityLevelProvider')]
    public function getSecurityLevel_returns_correct_value(HashAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getSecurityLevel());
    }

    public static function securityLevelProvider(): array
    {
        return [
            'SHA256 is 256-bit' => [HashAlgorithm::SHA256, 256],
            'SHA384 is 384-bit' => [HashAlgorithm::SHA384, 384],
            'SHA512 is 512-bit' => [HashAlgorithm::SHA512, 512],
            'BLAKE2B is 512-bit' => [HashAlgorithm::BLAKE2B, 512],
        ];
    }

    #[Test]
    #[DataProvider('nativeAlgorithmProvider')]
    public function getNativeAlgorithm_returns_correct_value(HashAlgorithm $algorithm, string $expected): void
    {
        $this->assertSame($expected, $algorithm->getNativeAlgorithm());
    }

    public static function nativeAlgorithmProvider(): array
    {
        return [
            'SHA256' => [HashAlgorithm::SHA256, 'sha256'],
            'SHA384' => [HashAlgorithm::SHA384, 'sha384'],
            'SHA512' => [HashAlgorithm::SHA512, 'sha512'],
            'BLAKE2B uses blake2b512' => [HashAlgorithm::BLAKE2B, 'blake2b512'],
        ];
    }

    #[Test]
    #[DataProvider('outputLengthProvider')]
    public function getOutputLength_returns_correct_value(HashAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getOutputLength());
    }

    public static function outputLengthProvider(): array
    {
        return [
            'SHA256 outputs 32 bytes' => [HashAlgorithm::SHA256, 32],
            'SHA384 outputs 48 bytes' => [HashAlgorithm::SHA384, 48],
            'SHA512 outputs 64 bytes' => [HashAlgorithm::SHA512, 64],
            'BLAKE2B outputs 64 bytes' => [HashAlgorithm::BLAKE2B, 64],
        ];
    }
}
