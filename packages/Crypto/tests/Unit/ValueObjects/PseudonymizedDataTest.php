<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PseudonymizedData::class)]
final class PseudonymizedDataTest extends TestCase
{
    // =====================================================
    // CONSTRUCTION TESTS
    // =====================================================

    public function test_construct_with_all_parameters(): void
    {
        $timestamp = new DateTimeImmutable('2024-06-20 10:00:00');
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_abc123xyz789',
            keyId: 'encryption-key-001',
            keyVersion: 2,
            pseudonymizedAt: $timestamp,
            metadata: ['context' => 'customer_pii'],
        );

        $this->assertSame('PSE_abc123xyz789', $pseudonymized->pseudonym);
        $this->assertSame('encryption-key-001', $pseudonymized->keyId);
        $this->assertSame(2, $pseudonymized->keyVersion);
        $this->assertSame($timestamp, $pseudonymized->pseudonymizedAt);
        $this->assertSame(['context' => 'customer_pii'], $pseudonymized->metadata);
    }

    public function test_construct_with_default_metadata(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_minimal',
            keyId: 'key-001',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->assertSame([], $pseudonymized->metadata);
    }

    public function test_construct_with_complex_metadata(): void
    {
        $metadata = [
            'entity' => 'customer',
            'field' => 'email',
            'algorithm' => 'aes-256-gcm',
            'tenant_id' => 'tenant-001',
            'nested' => ['key' => 'value'],
        ];

        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_complex',
            keyId: 'key-001',
            keyVersion: 5,
            pseudonymizedAt: new DateTimeImmutable(),
            metadata: $metadata,
        );

        $this->assertSame($metadata, $pseudonymized->metadata);
    }

    // =====================================================
    // KEY VERSION TESTS
    // =====================================================

    public function test_is_current_key_version_returns_true_when_matching(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_test',
            keyId: 'key-001',
            keyVersion: 3,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->assertTrue($pseudonymized->isCurrentKeyVersion(3));
    }

    public function test_is_current_key_version_returns_false_when_not_matching(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_test',
            keyId: 'key-001',
            keyVersion: 2,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->assertFalse($pseudonymized->isCurrentKeyVersion(3));
        $this->assertFalse($pseudonymized->isCurrentKeyVersion(1));
        $this->assertFalse($pseudonymized->isCurrentKeyVersion(0));
    }

    #[DataProvider('keyRotationProvider')]
    public function test_needs_key_rotation_with_various_scenarios(
        int $keyVersion,
        int $currentVersion,
        int $maxVersionsBehind,
        bool $expectedNeedsRotation
    ): void {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_rotation',
            keyId: 'key-001',
            keyVersion: $keyVersion,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $this->assertSame(
            $expectedNeedsRotation,
            $pseudonymized->needsKeyRotation($currentVersion, $maxVersionsBehind)
        );
    }

    public static function keyRotationProvider(): array
    {
        return [
            // [keyVersion, currentVersion, maxVersionsBehind, expectedNeedsRotation]
            'same version - no rotation needed' => [3, 3, 1, false],
            '1 behind with max 1 - no rotation needed' => [2, 3, 1, false],
            '2 behind with max 1 - needs rotation' => [1, 3, 1, true],
            '3 behind with max 1 - needs rotation' => [1, 4, 1, true],
            '2 behind with max 2 - no rotation needed' => [1, 3, 2, false],
            '3 behind with max 2 - needs rotation' => [1, 4, 2, true],
            'version 1 with current 1 - no rotation needed' => [1, 1, 1, false],
            'version 0 with current 5, max 3 - needs rotation' => [0, 5, 3, true],
        ];
    }

    public function test_needs_key_rotation_default_max_versions_behind(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_default',
            keyId: 'key-001',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        // Default maxVersionsBehind is 1
        $this->assertFalse($pseudonymized->needsKeyRotation(2)); // 2 - 1 = 1, not > 1
        $this->assertTrue($pseudonymized->needsKeyRotation(3));  // 3 - 1 = 2 > 1
    }

    // =====================================================
    // SERIALIZATION TESTS
    // =====================================================

    public function test_to_array_returns_correct_structure(): void
    {
        $timestamp = new DateTimeImmutable('2024-06-20T14:00:00+00:00');
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_serialize',
            keyId: 'key-001',
            keyVersion: 3,
            pseudonymizedAt: $timestamp,
            metadata: ['entity' => 'user', 'field' => 'email'],
        );

        $array = $pseudonymized->toArray();

        $this->assertArrayHasKey('pseudonym', $array);
        $this->assertArrayHasKey('key_id', $array);
        $this->assertArrayHasKey('key_version', $array);
        $this->assertArrayHasKey('pseudonymized_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('PSE_serialize', $array['pseudonym']);
        $this->assertSame('key-001', $array['key_id']);
        $this->assertSame(3, $array['key_version']);
        $this->assertSame($timestamp->format(DateTimeImmutable::ATOM), $array['pseudonymized_at']);
        $this->assertSame(['entity' => 'user', 'field' => 'email'], $array['metadata']);
    }

    public function test_to_array_with_empty_metadata(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_empty_meta',
            keyId: 'key-001',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $array = $pseudonymized->toArray();

        $this->assertSame([], $array['metadata']);
    }

    public function test_from_array_creates_object_correctly(): void
    {
        $data = [
            'pseudonym' => 'PSE_restored',
            'key_id' => 'key-002',
            'key_version' => 5,
            'pseudonymized_at' => '2024-06-20T14:00:00+00:00',
            'metadata' => ['restored' => true],
        ];

        $pseudonymized = PseudonymizedData::fromArray($data);

        $this->assertSame('PSE_restored', $pseudonymized->pseudonym);
        $this->assertSame('key-002', $pseudonymized->keyId);
        $this->assertSame(5, $pseudonymized->keyVersion);
        $this->assertSame('2024-06-20', $pseudonymized->pseudonymizedAt->format('Y-m-d'));
        $this->assertSame(['restored' => true], $pseudonymized->metadata);
    }

    public function test_from_array_handles_missing_optional_fields(): void
    {
        $data = [
            'pseudonym' => 'PSE_minimal',
            'key_id' => 'key-001',
            'key_version' => 1,
            'pseudonymized_at' => '2024-01-01T00:00:00+00:00',
        ];

        $pseudonymized = PseudonymizedData::fromArray($data);

        $this->assertSame([], $pseudonymized->metadata);
    }

    #[DataProvider('missingRequiredFieldProvider')]
    public function test_from_array_throws_for_missing_required_fields(
        array $data,
        string $missingField
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($missingField);

        PseudonymizedData::fromArray($data);
    }

    public static function missingRequiredFieldProvider(): array
    {
        $baseData = [
            'pseudonym' => 'PSE_test',
            'key_id' => 'key-001',
            'key_version' => 1,
            'pseudonymized_at' => '2024-01-01T00:00:00+00:00',
        ];

        return [
            'missing pseudonym' => [
                array_diff_key($baseData, ['pseudonym' => '']),
                'pseudonym',
            ],
            'missing key_id' => [
                array_diff_key($baseData, ['key_id' => '']),
                'key_id',
            ],
            'missing key_version' => [
                array_diff_key($baseData, ['key_version' => '']),
                'key_version',
            ],
            'missing pseudonymized_at' => [
                array_diff_key($baseData, ['pseudonymized_at' => '']),
                'pseudonymized_at',
            ],
        ];
    }

    public function test_from_array_throws_for_invalid_date_format(): void
    {
        $data = [
            'pseudonym' => 'PSE_invalid',
            'key_id' => 'key-001',
            'key_version' => 1,
            'pseudonymized_at' => 'invalid-date-format',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ATOM');

        PseudonymizedData::fromArray($data);
    }

    public function test_serialization_roundtrip(): void
    {
        $original = new PseudonymizedData(
            pseudonym: 'PSE_roundtrip',
            keyId: 'key-roundtrip',
            keyVersion: 7,
            pseudonymizedAt: new DateTimeImmutable('2024-06-20T12:00:00+00:00'),
            metadata: ['type' => 'email', 'tenant' => 'tenant-001'],
        );

        $array = $original->toArray();
        $restored = PseudonymizedData::fromArray($array);

        $this->assertSame($original->pseudonym, $restored->pseudonym);
        $this->assertSame($original->keyId, $restored->keyId);
        $this->assertSame($original->keyVersion, $restored->keyVersion);
        $this->assertSame($original->metadata, $restored->metadata);
        $this->assertEquals(
            $original->pseudonymizedAt->format('c'),
            $restored->pseudonymizedAt->format('c')
        );
    }

    // =====================================================
    // JSON SERIALIZATION TESTS
    // =====================================================

    public function test_json_serialize_returns_valid_json(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_json',
            keyId: 'key-001',
            keyVersion: 2,
            pseudonymizedAt: new DateTimeImmutable('2024-06-20T10:00:00+00:00'),
            metadata: ['entity' => 'user'],
        );

        $json = $pseudonymized->jsonSerialize();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('PSE_json', $decoded['pseudonym']);
        $this->assertSame('key-001', $decoded['key_id']);
        $this->assertSame(2, $decoded['key_version']);
    }

    // =====================================================
    // LOG IDENTIFIER TESTS
    // =====================================================

    public function test_get_log_identifier_returns_safe_format(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_sensitive_data_here',
            keyId: 'customer-key-001',
            keyVersion: 3,
            pseudonymizedAt: new DateTimeImmutable('2024-06-20 10:00:00'),
        );

        $logId = $pseudonymized->getLogIdentifier();

        // Should NOT contain the actual pseudonym (sensitive)
        $this->assertStringNotContainsString('PSE_sensitive', $logId);
        
        // Should contain keyId, version, and date
        $this->assertStringContainsString('customer-key-001', $logId);
        $this->assertStringContainsString('v3', $logId);
        $this->assertStringContainsString('2024-06-20', $logId);
        
        // Format: "keyId:v{version}@{date}"
        $this->assertMatchesRegularExpression('/^[\w-]+:v\d+@\d{4}-\d{2}-\d{2}$/', $logId);
    }

    // =====================================================
    // IMMUTABILITY TESTS
    // =====================================================

    public function test_properties_are_readonly(): void
    {
        $pseudonymized = new PseudonymizedData(
            pseudonym: 'PSE_immutable',
            keyId: 'key-001',
            keyVersion: 1,
            pseudonymizedAt: new DateTimeImmutable(),
        );

        $reflection = new \ReflectionClass($pseudonymized);
        
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(PseudonymizedData::class);
        
        $this->assertTrue($reflection->isFinal());
    }
}
