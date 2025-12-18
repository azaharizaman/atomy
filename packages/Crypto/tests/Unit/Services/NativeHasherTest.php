<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Services;

use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Services\NativeHasher;
use Nexus\Crypto\ValueObjects\HashResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Crypto\Services\NativeHasher
 */
final class NativeHasherTest extends TestCase
{
    private NativeHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new NativeHasher();
    }

    // =====================================================
    // HASH TESTS
    // =====================================================

    public function test_hash_with_sha256(): void
    {
        $data = 'Hello, World!';
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $this->assertInstanceOf(HashResult::class, $result);
        $this->assertSame(HashAlgorithm::SHA256, $result->algorithm);
        $this->assertSame(64, strlen($result->hash)); // 256 bits = 64 hex chars
        $this->assertNull($result->salt);
    }

    public function test_hash_with_sha384(): void
    {
        $data = 'Hello, World!';
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA384);
        
        $this->assertSame(HashAlgorithm::SHA384, $result->algorithm);
        $this->assertSame(96, strlen($result->hash)); // 384 bits = 96 hex chars
    }

    public function test_hash_with_sha512(): void
    {
        $data = 'Hello, World!';
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA512);
        
        $this->assertSame(HashAlgorithm::SHA512, $result->algorithm);
        $this->assertSame(128, strlen($result->hash)); // 512 bits = 128 hex chars
    }

    public function test_hash_with_blake2b(): void
    {
        $data = 'Hello, World!';
        
        $result = $this->hasher->hash($data, HashAlgorithm::BLAKE2B);
        
        $this->assertSame(HashAlgorithm::BLAKE2B, $result->algorithm);
        $this->assertSame(64, strlen($result->hash)); // 256 bits = 64 hex chars (default BLAKE2b output)
    }

    public function test_hash_defaults_to_sha256(): void
    {
        $data = 'Hello, World!';
        
        $result = $this->hasher->hash($data);
        
        $this->assertSame(HashAlgorithm::SHA256, $result->algorithm);
    }

    public function test_hash_is_deterministic(): void
    {
        $data = 'Same input';
        
        $result1 = $this->hasher->hash($data, HashAlgorithm::SHA256);
        $result2 = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $this->assertSame($result1->hash, $result2->hash);
    }

    public function test_hash_different_inputs_produce_different_hashes(): void
    {
        $result1 = $this->hasher->hash('input1', HashAlgorithm::SHA256);
        $result2 = $this->hasher->hash('input2', HashAlgorithm::SHA256);
        
        $this->assertNotSame($result1->hash, $result2->hash);
    }

    public function test_hash_produces_known_values(): void
    {
        // Test against known SHA-256 hash
        $data = 'test';
        $expectedHash = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $this->assertSame($expectedHash, $result->hash);
    }

    public function test_hash_handles_empty_string(): void
    {
        $result = $this->hasher->hash('', HashAlgorithm::SHA256);
        
        // SHA-256 of empty string is a known value
        $expectedHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
        
        $this->assertSame($expectedHash, $result->hash);
    }

    public function test_hash_handles_unicode(): void
    {
        $data = '你好世界 🌍 مرحبا';
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $this->assertInstanceOf(HashResult::class, $result);
        $this->assertSame(64, strlen($result->hash));
    }

    public function test_hash_handles_binary_data(): void
    {
        $data = random_bytes(256);
        
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $this->assertInstanceOf(HashResult::class, $result);
        $this->assertSame(64, strlen($result->hash));
    }

    // =====================================================
    // VERIFY TESTS
    // =====================================================

    public function test_verify_returns_true_for_matching_hash(): void
    {
        $data = 'Hello, World!';
        $hash = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        $result = $this->hasher->verify($data, $hash);
        
        $this->assertTrue($result);
    }

    public function test_verify_returns_false_for_non_matching_hash(): void
    {
        $hash = $this->hasher->hash('original data', HashAlgorithm::SHA256);
        
        $result = $this->hasher->verify('different data', $hash);
        
        $this->assertFalse($result);
    }

    public function test_verify_works_with_all_algorithms(): void
    {
        $data = 'Test data';
        
        foreach (HashAlgorithm::cases() as $algorithm) {
            $hash = $this->hasher->hash($data, $algorithm);
            
            $this->assertTrue($this->hasher->verify($data, $hash), "Failed for algorithm: {$algorithm->value}");
        }
    }

    public function test_verify_uses_constant_time_comparison(): void
    {
        $data = 'sensitive data';
        $hash = $this->hasher->hash($data, HashAlgorithm::SHA256);
        
        // Both correct and incorrect should take similar time
        // (we can't easily test timing, but we verify both work)
        $this->assertTrue($this->hasher->verify($data, $hash));
        $this->assertFalse($this->hasher->verify('wrong data', $hash));
    }

    // =====================================================
    // ALGORITHM TESTS
    // =====================================================

    /**
     * @dataProvider algorithmProvider
     */
    public function test_all_algorithms_produce_correct_length(HashAlgorithm $algorithm, int $expectedLength): void
    {
        $data = 'Test data';
        
        $result = $this->hasher->hash($data, $algorithm);
        
        $this->assertSame($expectedLength, strlen($result->hash));
    }

    public static function algorithmProvider(): array
    {
        return [
            'SHA-256' => [HashAlgorithm::SHA256, 64],
            'SHA-384' => [HashAlgorithm::SHA384, 96],
            'SHA-512' => [HashAlgorithm::SHA512, 128],
            'BLAKE2B' => [HashAlgorithm::BLAKE2B, 64],
        ];
    }

    // =====================================================
    // INTERFACE COMPLIANCE TESTS
    // =====================================================

    public function test_implements_hasher_interface(): void
    {
        $this->assertInstanceOf(HasherInterface::class, $this->hasher);
    }

    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(NativeHasher::class);
        
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
