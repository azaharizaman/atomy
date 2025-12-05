<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\Contracts;

use Nexus\Common\Contracts\UlidInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class UlidInterfaceTest extends TestCase
{
    private UlidInterface $ulidGenerator;

    protected function setUp(): void
    {
        // Use a concrete implementation for testing (Symfony ULID adapter)
        $this->ulidGenerator = new class implements UlidInterface {
            public function generate(): string
            {
                return (string) new Ulid();
            }

            public function isValid(string $ulid): bool
            {
                return Ulid::isValid($ulid);
            }

            public function getTimestamp(string $ulid): \DateTimeImmutable
            {
                if (!$this->isValid($ulid)) {
                    throw new \InvalidArgumentException("Invalid ULID: {$ulid}");
                }
                
                return Ulid::fromString($ulid)->getDateTime();
            }
        };
    }

    public function test_generate_creates_valid_ulid(): void
    {
        $ulid = $this->ulidGenerator->generate();
        
        $this->assertIsString($ulid);
        $this->assertSame(26, strlen($ulid));
        $this->assertTrue($this->ulidGenerator->isValid($ulid));
    }

    public function test_generate_creates_unique_ulids(): void
    {
        $ulid1 = $this->ulidGenerator->generate();
        $ulid2 = $this->ulidGenerator->generate();
        
        $this->assertNotEquals($ulid1, $ulid2);
    }

    public function test_is_valid_returns_true_for_valid_ulid(): void
    {
        $validUlid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        
        $this->assertTrue($this->ulidGenerator->isValid($validUlid));
    }

    public function test_is_valid_returns_false_for_invalid_ulid(): void
    {
        $invalidUlids = [
            'invalid',
            '123',
            '',
            'ZZZZZZZZZZZZZZZZZZZZZZZZZ', // Too short
            '01ARZ3NDEKTSV4RRFFQ69G5FAVX', // Too long
            'NOT-A-VALID-ULID-FORMAT!!',
        ];
        
        foreach ($invalidUlids as $invalid) {
            $this->assertFalse($this->ulidGenerator->isValid($invalid), "Failed for: {$invalid}");
        }
    }

    public function test_get_timestamp_extracts_timestamp_from_ulid(): void
    {
        $ulid = $this->ulidGenerator->generate();
        $timestamp = $this->ulidGenerator->getTimestamp($ulid);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
        
        // Timestamp should be recent (within last few seconds)
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $timestamp->getTimestamp();
        $this->assertLessThan(5, abs($diff));
    }

    public function test_get_timestamp_throws_for_invalid_ulid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID');
        
        $this->ulidGenerator->getTimestamp('invalid-ulid');
    }

    public function test_ulids_are_lexicographically_sortable(): void
    {
        // Generate ULIDs with slight delay to ensure different timestamps
        $ulid1 = $this->ulidGenerator->generate();
        usleep(1000); // 1ms delay
        $ulid2 = $this->ulidGenerator->generate();
        usleep(1000);
        $ulid3 = $this->ulidGenerator->generate();
        
        $ulids = [$ulid3, $ulid1, $ulid2];
        sort($ulids);
        
        // After sorting, they should be in chronological order
        $this->assertSame($ulid1, $ulids[0]);
        $this->assertSame($ulid2, $ulids[1]);
        $this->assertSame($ulid3, $ulids[2]);
    }

    public function test_generated_ulids_are_26_characters_uppercase(): void
    {
        $ulid = $this->ulidGenerator->generate();
        
        $this->assertSame(26, strlen($ulid));
        $this->assertSame($ulid, strtoupper($ulid), 'ULID should be uppercase');
    }

    public function test_is_valid_accepts_lowercase_ulids(): void
    {
        $uppercaseUlid = $this->ulidGenerator->generate();
        $lowercaseUlid = strtolower($uppercaseUlid);
        
        $this->assertTrue($this->ulidGenerator->isValid($lowercaseUlid));
    }
}
