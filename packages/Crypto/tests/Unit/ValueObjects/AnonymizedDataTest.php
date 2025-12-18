<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use PHPUnit\Framework\TestCase;

final class AnonymizedDataTest extends TestCase
{
    // =====================================================
    // CONSTRUCTION TESTS
    // =====================================================

    public function test_construct_with_minimal_parameters(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: 'abc123hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable('2024-01-15 10:30:00'),
        );

        $this->assertSame('abc123hash', $anonymized->anonymizedValue);
        $this->assertSame(AnonymizationMethod::HASH_BASED, $anonymized->method);
        $this->assertSame('2024-01-15 10:30:00', $anonymized->anonymizedAt->format('Y-m-d H:i:s'));
        $this->assertNull($anonymized->salt);
        $this->assertSame([], $anonymized->metadata);
    }

    public function test_construct_with_salt(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: 'salted_hash_value',
            method: AnonymizationMethod::SALTED_HASH,
            anonymizedAt: new DateTimeImmutable(),
            salt: 'random_salt_here',
        );

        $this->assertSame('random_salt_here', $anonymized->salt);
    }

    public function test_construct_with_metadata(): void
    {
        $metadata = [
            'security_level' => 'high',
            'processor' => 'anonymizer-v1',
        ];

        $anonymized = new AnonymizedData(
            anonymizedValue: 'hashed',
            method: AnonymizationMethod::HMAC_BASED,
            anonymizedAt: new DateTimeImmutable(),
            metadata: $metadata,
        );

        $this->assertSame($metadata, $anonymized->metadata);
    }

    // =====================================================
    // IS DETERMINISTIC TESTS
    // =====================================================

    public function test_is_deterministic_returns_true_for_hash_based(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: 'hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertTrue($anonymized->isDeterministic());
    }

    public function test_is_deterministic_returns_false_for_salted_hash(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: 'salted',
            method: AnonymizationMethod::SALTED_HASH,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertFalse($anonymized->isDeterministic());
    }

    public function test_is_deterministic_returns_true_for_suppression(): void
    {
        $anonymized = new AnonymizedData(
            anonymizedValue: '[SUPPRESSED]',
            method: AnonymizationMethod::SUPPRESSION,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertTrue($anonymized->isDeterministic());
    }

    // =====================================================
    // IS CORRELATABLE TESTS
    // =====================================================

    public function test_is_correlatable_returns_true_for_deterministic_methods(): void
    {
        $hashBased = new AnonymizedData(
            anonymizedValue: 'hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $hmacBased = new AnonymizedData(
            anonymizedValue: 'hmac',
            method: AnonymizationMethod::HMAC_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertTrue($hashBased->isCorrelatable());
        $this->assertTrue($hmacBased->isCorrelatable());
    }

    public function test_is_correlatable_returns_false_for_non_deterministic_methods(): void
    {
        $saltedHash = new AnonymizedData(
            anonymizedValue: 'salted',
            method: AnonymizationMethod::SALTED_HASH,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertFalse($saltedHash->isCorrelatable());
    }

    public function test_is_correlatable_returns_false_for_suppression(): void
    {
        $suppressed = new AnonymizedData(
            anonymizedValue: '[SUPPRESSED]',
            method: AnonymizationMethod::SUPPRESSION,
            anonymizedAt: new DateTimeImmutable(),
        );

        // Suppression is deterministic but NOT correlatable (all data looks the same)
        $this->assertFalse($suppressed->isCorrelatable());
    }

    // =====================================================
    // SERIALIZATION TESTS
    // =====================================================

    public function test_to_array_returns_correct_structure(): void
    {
        $timestamp = new DateTimeImmutable('2024-06-20 14:00:00');
        $anonymized = new AnonymizedData(
            anonymizedValue: 'test_hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: $timestamp,
            salt: 'test_salt',
            metadata: ['key' => 'value'],
        );

        $array = $anonymized->toArray();

        $this->assertSame('test_hash', $array['anonymized_value']);
        $this->assertSame('hash_based', $array['method']);
        $this->assertSame($timestamp->format(DATE_ATOM), $array['anonymized_at']);
        $this->assertSame('test_salt', $array['salt']);
        $this->assertSame(['key' => 'value'], $array['metadata']);
    }

    public function test_from_array_creates_object_correctly(): void
    {
        $data = [
            'anonymized_value' => 'restored_hash',
            'method' => 'salted_hash',
            'anonymized_at' => '2024-06-20T14:00:00+00:00',
            'salt' => 'restored_salt',
            'metadata' => ['restored' => true],
        ];

        $anonymized = AnonymizedData::fromArray($data);

        $this->assertSame('restored_hash', $anonymized->anonymizedValue);
        $this->assertSame(AnonymizationMethod::SALTED_HASH, $anonymized->method);
        $this->assertSame('2024-06-20', $anonymized->anonymizedAt->format('Y-m-d'));
        $this->assertSame('restored_salt', $anonymized->salt);
        $this->assertSame(['restored' => true], $anonymized->metadata);
    }

    public function test_from_array_handles_missing_optional_fields(): void
    {
        $data = [
            'anonymized_value' => 'minimal_hash',
            'method' => 'suppression',
            'anonymized_at' => '2024-01-01T00:00:00+00:00',
        ];

        $anonymized = AnonymizedData::fromArray($data);

        $this->assertNull($anonymized->salt);
        $this->assertSame([], $anonymized->metadata);
    }

    // =====================================================
    // EQUALITY TESTS
    // =====================================================

    public function test_equals_returns_true_for_same_value_and_method(): void
    {
        $anonymized1 = new AnonymizedData(
            anonymizedValue: 'same_hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable('2024-01-01'),
        );

        $anonymized2 = new AnonymizedData(
            anonymizedValue: 'same_hash',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable('2024-12-31'), // Different date
        );

        $this->assertTrue($anonymized1->equals($anonymized2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $anonymized1 = new AnonymizedData(
            anonymizedValue: 'hash_a',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $anonymized2 = new AnonymizedData(
            anonymizedValue: 'hash_b',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertFalse($anonymized1->equals($anonymized2));
    }

    public function test_equals_returns_false_for_different_methods(): void
    {
        $anonymized1 = new AnonymizedData(
            anonymizedValue: 'same_value',
            method: AnonymizationMethod::HASH_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $anonymized2 = new AnonymizedData(
            anonymizedValue: 'same_value',
            method: AnonymizationMethod::HMAC_BASED,
            anonymizedAt: new DateTimeImmutable(),
        );

        $this->assertFalse($anonymized1->equals($anonymized2));
    }
}
