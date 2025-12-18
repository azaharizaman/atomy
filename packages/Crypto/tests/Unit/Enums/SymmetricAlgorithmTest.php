<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Enums;

use Nexus\Crypto\Enums\SymmetricAlgorithm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SymmetricAlgorithm::class)]
final class SymmetricAlgorithmTest extends TestCase
{
    #[Test]
    public function all_cases_have_valid_string_values(): void
    {
        $this->assertSame('aes-256-gcm', SymmetricAlgorithm::AES256GCM->value);
        $this->assertSame('aes-256-cbc', SymmetricAlgorithm::AES256CBC->value);
        $this->assertSame('chacha20-poly1305', SymmetricAlgorithm::CHACHA20POLY1305->value);
    }

    #[Test]
    #[DataProvider('authenticatedAlgorithmsProvider')]
    public function isAuthenticated_returns_correct_value(SymmetricAlgorithm $algorithm, bool $expected): void
    {
        $this->assertSame($expected, $algorithm->isAuthenticated());
    }

    public static function authenticatedAlgorithmsProvider(): array
    {
        return [
            'AES256GCM is authenticated' => [SymmetricAlgorithm::AES256GCM, true],
            'AES256CBC is not authenticated' => [SymmetricAlgorithm::AES256CBC, false],
            'CHACHA20POLY1305 is authenticated' => [SymmetricAlgorithm::CHACHA20POLY1305, true],
        ];
    }

    #[Test]
    public function all_algorithms_require_iv(): void
    {
        foreach (SymmetricAlgorithm::cases() as $algorithm) {
            $this->assertTrue(
                $algorithm->requiresIV(),
                "{$algorithm->name} should require IV"
            );
        }
    }

    #[Test]
    #[DataProvider('keyLengthProvider')]
    public function getKeyLength_returns_correct_value(SymmetricAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getKeyLength());
    }

    public static function keyLengthProvider(): array
    {
        return [
            'AES256GCM needs 32 bytes' => [SymmetricAlgorithm::AES256GCM, 32],
            'AES256CBC needs 32 bytes' => [SymmetricAlgorithm::AES256CBC, 32],
            'CHACHA20POLY1305 needs 32 bytes' => [SymmetricAlgorithm::CHACHA20POLY1305, 32],
        ];
    }

    #[Test]
    #[DataProvider('ivLengthProvider')]
    public function getIVLength_returns_correct_value(SymmetricAlgorithm $algorithm, int $expected): void
    {
        $this->assertSame($expected, $algorithm->getIVLength());
    }

    public static function ivLengthProvider(): array
    {
        return [
            'AES256GCM uses 12 bytes' => [SymmetricAlgorithm::AES256GCM, 12],
            'AES256CBC uses 16 bytes' => [SymmetricAlgorithm::AES256CBC, 16],
            'CHACHA20POLY1305 uses 12 bytes' => [SymmetricAlgorithm::CHACHA20POLY1305, 12],
        ];
    }

    #[Test]
    #[DataProvider('tagLengthProvider')]
    public function getTagLength_returns_correct_value(SymmetricAlgorithm $algorithm, ?int $expected): void
    {
        $this->assertSame($expected, $algorithm->getTagLength());
    }

    public static function tagLengthProvider(): array
    {
        return [
            'AES256GCM has 16-byte tag' => [SymmetricAlgorithm::AES256GCM, 16],
            'AES256CBC has no tag' => [SymmetricAlgorithm::AES256CBC, null],
            'CHACHA20POLY1305 has 16-byte tag' => [SymmetricAlgorithm::CHACHA20POLY1305, 16],
        ];
    }

    #[Test]
    #[DataProvider('openSSLMethodProvider')]
    public function getOpenSSLMethod_returns_correct_value(SymmetricAlgorithm $algorithm, string $expected): void
    {
        $this->assertSame($expected, $algorithm->getOpenSSLMethod());
    }

    public static function openSSLMethodProvider(): array
    {
        return [
            'AES256GCM' => [SymmetricAlgorithm::AES256GCM, 'aes-256-gcm'],
            'AES256CBC' => [SymmetricAlgorithm::AES256CBC, 'aes-256-cbc'],
            'CHACHA20POLY1305' => [SymmetricAlgorithm::CHACHA20POLY1305, 'chacha20-poly1305'],
        ];
    }

    #[Test]
    public function all_256bit_algorithms_are_quantum_resistant(): void
    {
        foreach (SymmetricAlgorithm::cases() as $algorithm) {
            $this->assertTrue(
                $algorithm->isQuantumResistant(),
                "{$algorithm->name} should be quantum resistant"
            );
        }
    }
}
