<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Payment\Exceptions\InvalidIdempotencyKeyException;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdempotencyKey::class)]
final class IdempotencyKeyTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_key_and_times(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');
        $expiresAt = new \DateTimeImmutable('2024-01-16 10:00:00');

        $key = new IdempotencyKey('test-key', $createdAt, $expiresAt);

        $this->assertSame('test-key', $key->key);
        $this->assertSame($createdAt, $key->createdAt);
        $this->assertSame($expiresAt, $key->expiresAt);
    }

    #[Test]
    public function it_throws_exception_for_empty_key(): void
    {
        $this->expectException(InvalidIdempotencyKeyException::class);
        $this->expectExceptionMessage('cannot be empty');

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');

        new IdempotencyKey('', $createdAt, $expiresAt);
    }

    #[Test]
    public function it_throws_exception_for_whitespace_only_key(): void
    {
        $this->expectException(InvalidIdempotencyKeyException::class);
        $this->expectExceptionMessage('cannot be empty');

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');

        new IdempotencyKey('   ', $createdAt, $expiresAt);
    }

    #[Test]
    public function it_throws_exception_for_key_exceeding_255_characters(): void
    {
        $this->expectException(InvalidIdempotencyKeyException::class);
        $this->expectExceptionMessage('cannot exceed 255 characters');

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');

        new IdempotencyKey(str_repeat('x', 256), $createdAt, $expiresAt);
    }

    #[Test]
    public function it_accepts_key_with_exactly_255_characters(): void
    {
        $key = str_repeat('x', 255);
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');

        $idempotencyKey = new IdempotencyKey($key, $createdAt, $expiresAt);

        $this->assertSame($key, $idempotencyKey->key);
    }

    #[Test]
    public function it_throws_exception_when_expiry_before_creation(): void
    {
        $this->expectException(InvalidIdempotencyKeyException::class);
        $this->expectExceptionMessage('Expiry time must be after creation time');

        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');
        $expiresAt = new \DateTimeImmutable('2024-01-15 09:00:00');

        new IdempotencyKey('test-key', $createdAt, $expiresAt);
    }

    #[Test]
    public function it_throws_exception_when_expiry_equals_creation(): void
    {
        $this->expectException(InvalidIdempotencyKeyException::class);
        $this->expectExceptionMessage('Expiry time must be after creation time');

        $time = new \DateTimeImmutable();

        new IdempotencyKey('test-key', $time, $time);
    }

    #[Test]
    public function it_can_generate_key_with_default_ttl(): void
    {
        $before = new \DateTimeImmutable();
        $key = IdempotencyKey::generate();
        $after = new \DateTimeImmutable();

        $this->assertNotEmpty($key->key);
        $this->assertSame(32, strlen($key->key)); // 16 bytes = 32 hex chars
        $this->assertGreaterThanOrEqual($before, $key->createdAt);
        $this->assertLessThanOrEqual($after, $key->createdAt);

        // Default TTL is 24 hours
        $expectedExpiry = $key->createdAt->modify('+24 hours');
        $this->assertEquals($expectedExpiry, $key->expiresAt);
    }

    #[Test]
    public function it_can_generate_key_with_custom_ttl(): void
    {
        $key = IdempotencyKey::generate(48);

        $expectedExpiry = $key->createdAt->modify('+48 hours');
        $this->assertEquals($expectedExpiry, $key->expiresAt);
    }

    #[Test]
    public function it_can_create_from_string(): void
    {
        $key = IdempotencyKey::fromString('my-custom-key');

        $this->assertSame('my-custom-key', $key->key);
    }

    #[Test]
    public function it_can_create_from_string_with_custom_ttl(): void
    {
        $key = IdempotencyKey::fromString('my-custom-key', 12);

        $expectedExpiry = $key->createdAt->modify('+12 hours');
        $this->assertEquals($expectedExpiry, $key->expiresAt);
    }

    #[Test]
    public function it_can_create_from_params(): void
    {
        $params = ['amount' => 100.00, 'currency' => 'USD', 'reference' => 'INV-001'];
        $key = IdempotencyKey::fromParams($params);

        // Key should be deterministic for same params
        $key2 = IdempotencyKey::fromParams($params);

        $this->assertSame($key->key, $key2->key);
    }

    #[Test]
    public function it_checks_if_expired(): void
    {
        $createdAt = new \DateTimeImmutable('2020-01-01 00:00:00');
        $expiresAt = new \DateTimeImmutable('2020-01-02 00:00:00');
        $expiredKey = new IdempotencyKey('old-key', $createdAt, $expiresAt);

        $this->assertTrue($expiredKey->isExpired());
    }

    #[Test]
    public function it_checks_if_not_expired(): void
    {
        $key = IdempotencyKey::generate();

        $this->assertFalse($key->isExpired());
    }

    #[Test]
    public function it_checks_if_valid(): void
    {
        $key = IdempotencyKey::generate();

        $this->assertTrue($key->isValid());
    }

    #[Test]
    public function it_checks_if_invalid_when_expired(): void
    {
        $createdAt = new \DateTimeImmutable('2020-01-01 00:00:00');
        $expiresAt = new \DateTimeImmutable('2020-01-02 00:00:00');
        $expiredKey = new IdempotencyKey('old-key', $createdAt, $expiresAt);

        $this->assertFalse($expiredKey->isValid());
    }

    #[Test]
    public function it_compares_equal_keys(): void
    {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify('+24 hours');

        $key1 = new IdempotencyKey('same-key', $createdAt, $expiresAt);
        $key2 = new IdempotencyKey('same-key', $createdAt, $expiresAt);

        $this->assertTrue($key1->equals($key2));
    }

    #[Test]
    public function it_compares_different_keys(): void
    {
        $key1 = IdempotencyKey::fromString('key-1');
        $key2 = IdempotencyKey::fromString('key-2');

        $this->assertFalse($key1->equals($key2));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $key = IdempotencyKey::fromString('test-key');

        $this->assertSame('test-key', (string) $key);
    }
}
