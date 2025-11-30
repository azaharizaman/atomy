<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Identity\ValueObjects\BackupCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(BackupCode::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class BackupCodeTest extends TestCase
{
    #[Test]
    public function it_creates_valid_backup_code_without_hash(): void
    {
        $code = new BackupCode(code: 'ABCD1234');

        $this->assertSame('ABCD1234', $code->code);
        $this->assertNull($code->hash);
        $this->assertNull($code->consumedAt);
        $this->assertFalse($code->isConsumed());
    }

    #[Test]
    public function it_creates_valid_backup_code_with_hash(): void
    {
        $hash = '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hashedvalue';
        $code = new BackupCode(
            code: 'ABCD1234',
            hash: $hash
        );

        $this->assertSame('ABCD1234', $code->code);
        $this->assertSame($hash, $code->hash);
        $this->assertFalse($code->isConsumed());
    }

    #[Test]
    public function it_creates_valid_backup_code_with_consumed_timestamp(): void
    {
        $consumedAt = new DateTimeImmutable('2024-01-15 10:30:00');
        $code = new BackupCode(
            code: 'ABCD1234',
            consumedAt: $consumedAt
        );

        $this->assertTrue($code->isConsumed());
        $this->assertSame($consumedAt, $code->consumedAt);
    }

    #[Test]
    public function it_rejects_code_shorter_than_8_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code must be at least 8 characters long');

        new BackupCode(code: 'SHORT');
    }

    #[Test]
    #[DataProvider('invalidCodeFormatProvider')]
    public function it_rejects_invalid_code_format(string $invalidCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code must contain only uppercase letters and numbers');

        new BackupCode(code: $invalidCode);
    }

    public static function invalidCodeFormatProvider(): array
    {
        return [
            'Lowercase letters' => ['abcd1234'],
            'Special characters' => ['ABCD-1234'],
            'Spaces' => ['ABCD 1234'],
            'Symbols' => ['ABCD!234'],
            'Underscores' => ['ABCD_1234'],
        ];
    }

    #[Test]
    public function it_rejects_invalid_hash_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code hash must be Argon2id format');

        new BackupCode(
            code: 'ABCD1234',
            hash: '$2y$10$invalid_bcrypt_hash'  // bcrypt, not Argon2id
        );
    }

    #[Test]
    public function it_marks_code_as_consumed(): void
    {
        $code = new BackupCode(code: 'ABCD1234');
        $timestamp = new DateTimeImmutable('2024-01-15 10:30:00');

        $consumed = $code->consume($timestamp);

        $this->assertFalse($code->isConsumed());  // Original is immutable
        $this->assertTrue($consumed->isConsumed());
        $this->assertSame($timestamp, $consumed->consumedAt);
        $this->assertSame('ABCD1234', $consumed->code);  // Code preserved
    }

    #[Test]
    public function it_rejects_consuming_already_consumed_code(): void
    {
        $code = new BackupCode(
            code: 'ABCD1234',
            consumedAt: new DateTimeImmutable('2024-01-15 10:00:00')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backup code has already been consumed');

        $code->consume(new DateTimeImmutable('2024-01-15 11:00:00'));
    }

    #[Test]
    public function it_adds_hash_to_code(): void
    {
        $code = new BackupCode(code: 'ABCD1234');
        $hash = '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hashedvalue';

        $withHash = $code->withHash($hash);

        $this->assertNull($code->hash);  // Original unchanged
        $this->assertSame($hash, $withHash->hash);
        $this->assertSame('ABCD1234', $withHash->code);  // Code preserved
        $this->assertNull($withHash->consumedAt);  // Consumption state preserved
    }

    #[Test]
    public function it_preserves_consumption_state_when_adding_hash(): void
    {
        $consumedAt = new DateTimeImmutable('2024-01-15 10:30:00');
        $code = new BackupCode(
            code: 'ABCD1234',
            consumedAt: $consumedAt
        );

        $hash = '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hashedvalue';
        $withHash = $code->withHash($hash);

        $this->assertTrue($withHash->isConsumed());
        $this->assertSame($consumedAt, $withHash->consumedAt);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $code = new BackupCode(
            code: 'ABCD1234',
            hash: '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hashedvalue'
        );

        $array = $code->toArray();

        $this->assertSame([
            'code' => 'ABCD1234',
            'hash' => '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hashedvalue',
            'consumed_at' => null,
        ], $array);
    }

    #[Test]
    public function it_converts_consumed_code_to_array(): void
    {
        $consumedAt = new DateTimeImmutable('2024-01-15 10:30:00');
        $code = new BackupCode(
            code: 'ABCD1234',
            consumedAt: $consumedAt
        );

        $array = $code->toArray();

        $this->assertSame('2024-01-15 10:30:00', $array['consumed_at']);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $code = new BackupCode(code: 'ABCD1234');
        
        $reflection = new ReflectionClass($code);
        
        // Verify all properties are readonly
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    #[Test]
    public function it_handles_long_codes(): void
    {
        $longCode = 'ABCD1234EFGH5678';  // 16 characters
        
        $code = new BackupCode(code: $longCode);

        $this->assertSame($longCode, $code->code);
    }

    #[Test]
    public function it_handles_numeric_only_codes(): void
    {
        $numericCode = '12345678';
        
        $code = new BackupCode(code: $numericCode);

        $this->assertSame($numericCode, $code->code);
    }

    #[Test]
    public function it_handles_alpha_only_codes(): void
    {
        $alphaCode = 'ABCDEFGH';
        
        $code = new BackupCode(code: $alphaCode);

        $this->assertSame($alphaCode, $code->code);
    }
}
