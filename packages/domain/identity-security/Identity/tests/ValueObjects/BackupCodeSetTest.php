<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Identity\ValueObjects\BackupCode;
use Nexus\Identity\ValueObjects\BackupCodeSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(BackupCodeSet::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class BackupCodeSetTest extends TestCase
{
    #[Test]
    public function it_creates_valid_backup_code_set(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678'),
            new BackupCode(code: 'IJKL9012'),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertSame($codes, $set->codes);
        $this->assertSame(3, $set->count());
    }

    #[Test]
    public function it_rejects_empty_code_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code set cannot be empty');

        new BackupCodeSet([]);
    }

    #[Test]
    public function it_rejects_non_backup_code_items(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All items in set must be BackupCode instances');

        new BackupCodeSet(['ABCD1234', 'EFGH5678']);  // Strings instead of BackupCode objects
    }

    #[Test]
    public function it_rejects_duplicate_codes(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'ABCD1234'),  // Duplicate
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code set contains duplicate codes');

        new BackupCodeSet($codes);
    }

    #[Test]
    public function it_counts_remaining_codes(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
            new BackupCode(code: 'IJKL9012'),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertSame(2, $set->getRemainingCount());
    }

    #[Test]
    public function it_counts_consumed_codes(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
            new BackupCode(code: 'IJKL9012', consumedAt: new DateTimeImmutable()),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertSame(2, $set->getConsumedCount());
    }

    #[Test]
    #[DataProvider('regenerationThresholdProvider')]
    public function it_identifies_when_regeneration_should_be_triggered(
        int $remaining,
        int $threshold,
        bool $shouldTrigger
    ): void {
        $codes = [];
        
        // Create remaining unconsumed codes
        for ($i = 0; $i < $remaining; $i++) {
            $codes[] = new BackupCode(code: sprintf('CODE%04d', $i));
        }
        
        // Add some consumed codes to make total 10
        for ($i = $remaining; $i < 10; $i++) {
            $codes[] = new BackupCode(
                code: sprintf('CODE%04d', $i),
                consumedAt: new DateTimeImmutable()
            );
        }

        $set = new BackupCodeSet($codes);

        $this->assertSame($shouldTrigger, $set->shouldTriggerRegeneration($threshold));
    }

    public static function regenerationThresholdProvider(): array
    {
        return [
            '0 remaining, threshold 2 → trigger' => [0, 2, true],
            '1 remaining, threshold 2 → trigger' => [1, 2, true],
            '2 remaining, threshold 2 → trigger' => [2, 2, true],
            '3 remaining, threshold 2 → no trigger' => [3, 2, false],
            '5 remaining, threshold 3 → no trigger' => [5, 3, false],
            '3 remaining, threshold 3 → trigger' => [3, 3, true],
        ];
    }

    #[Test]
    public function it_uses_default_threshold_of_2_for_regeneration(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678'),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertTrue($set->shouldTriggerRegeneration());  // Default threshold = 2
    }

    #[Test]
    public function it_identifies_fully_consumed_set(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234', consumedAt: new DateTimeImmutable()),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertTrue($set->isFullyConsumed());
        $this->assertFalse($set->isUnused());
    }

    #[Test]
    public function it_identifies_unused_set(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678'),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertTrue($set->isUnused());
        $this->assertFalse($set->isFullyConsumed());
    }

    #[Test]
    public function it_identifies_partially_used_set(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertFalse($set->isUnused());
        $this->assertFalse($set->isFullyConsumed());
    }

    #[Test]
    public function it_finds_code_by_plaintext(): void
    {
        $targetCode = new BackupCode(code: 'EFGH5678');
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            $targetCode,
            new BackupCode(code: 'IJKL9012'),
        ];

        $set = new BackupCodeSet($codes);

        $found = $set->findCode('EFGH5678');

        $this->assertNotNull($found);
        $this->assertSame('EFGH5678', $found->code);
    }

    #[Test]
    public function it_returns_null_when_code_not_found(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678'),
        ];

        $set = new BackupCodeSet($codes);

        $found = $set->findCode('NONEXISTENT');

        $this->assertNull($found);
    }

    #[Test]
    public function it_gets_remaining_codes(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
            new BackupCode(code: 'IJKL9012'),
        ];

        $set = new BackupCodeSet($codes);

        $remaining = $set->getRemainingCodes();

        $this->assertCount(2, $remaining);
        $this->assertSame('ABCD1234', $remaining[0]->code);
        $this->assertSame('IJKL9012', $remaining[1]->code);
    }

    #[Test]
    public function it_gets_consumed_codes(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable()),
            new BackupCode(code: 'IJKL9012', consumedAt: new DateTimeImmutable()),
        ];

        $set = new BackupCodeSet($codes);

        $consumed = $set->getConsumedCodes();

        $this->assertCount(2, $consumed);
        $this->assertSame('EFGH5678', $consumed[0]->code);
        $this->assertSame('IJKL9012', $consumed[1]->code);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
            new BackupCode(code: 'EFGH5678', consumedAt: new DateTimeImmutable('2024-01-15 10:30:00')),
        ];

        $set = new BackupCodeSet($codes);

        $array = $set->toArray();

        $this->assertSame(2, $array['total']);
        $this->assertSame(1, $array['remaining']);
        $this->assertSame(1, $array['consumed']);
        $this->assertCount(2, $array['codes']);
        $this->assertSame('ABCD1234', $array['codes'][0]['code']);
        $this->assertSame('EFGH5678', $array['codes'][1]['code']);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
        ];

        $set = new BackupCodeSet($codes);
        
        $reflection = new ReflectionClass($set);
        
        // Verify all properties are readonly
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    #[Test]
    public function it_handles_large_code_sets(): void
    {
        $codes = [];
        
        // Create 100 codes
        for ($i = 0; $i < 100; $i++) {
            $codes[] = new BackupCode(code: sprintf('CODE%04d', $i));
        }

        $set = new BackupCodeSet($codes);

        $this->assertSame(100, $set->count());
        $this->assertSame(100, $set->getRemainingCount());
    }

    #[Test]
    public function it_handles_single_code_set(): void
    {
        $codes = [
            new BackupCode(code: 'ABCD1234'),
        ];

        $set = new BackupCodeSet($codes);

        $this->assertSame(1, $set->count());
    }
}
