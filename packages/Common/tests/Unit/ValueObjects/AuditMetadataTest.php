<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\AuditMetadata;
use PHPUnit\Framework\TestCase;

final class AuditMetadataTest extends TestCase
{
    public function test_for_create_initializes_metadata(): void
    {
        $userId = 'user-123';
        $beforeTime = new DateTimeImmutable();
        
        $metadata = AuditMetadata::forCreate($userId);
        
        $afterTime = new DateTimeImmutable();
        
        $this->assertSame($userId, $metadata->getCreatedBy());
        $this->assertNull($metadata->getUpdatedBy());
        $this->assertGreaterThanOrEqual($beforeTime, $metadata->getCreatedAt());
        $this->assertLessThanOrEqual($afterTime, $metadata->getCreatedAt());
        $this->assertNull($metadata->getUpdatedAt());
    }

    public function test_with_update_creates_new_instance_with_update_info(): void
    {
        $createdUserId = 'user-123';
        $updatedUserId = 'user-456';
        
        $original = AuditMetadata::forCreate($createdUserId);
        
        sleep(1); // Ensure timestamps differ
        
        $updated = $original->withUpdate($updatedUserId);
        
        $this->assertSame($createdUserId, $updated->getCreatedBy());
        $this->assertSame($updatedUserId, $updated->getUpdatedBy());
        $this->assertSame($original->getCreatedAt(), $updated->getCreatedAt());
        $this->assertNotNull($updated->getUpdatedAt());
        $this->assertGreaterThan($updated->getCreatedAt(), $updated->getUpdatedAt());
    }

    public function test_with_update_preserves_immutability(): void
    {
        $original = AuditMetadata::forCreate('user-123');
        $updated = $original->withUpdate('user-456');
        
        $this->assertNotSame($original, $updated);
        $this->assertNull($original->getUpdatedBy());
        $this->assertSame('user-456', $updated->getUpdatedBy());
    }

    public function test_to_array(): void
    {
        $metadata = AuditMetadata::forCreate('user-123');
        $array = $metadata->toArray();
        
        $this->assertArrayHasKey('created_by', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_by', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertSame('user-123', $array['created_by']);
        $this->assertNull($array['updated_by']);
    }

    public function test_to_string(): void
    {
        $metadata = AuditMetadata::forCreate('user-123');
        $string = $metadata->toString();
        
        $this->assertStringContainsString('user-123', $string);
        $this->assertStringContainsString('Created by', $string);
    }

    public function test_from_array_with_create_only(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $data = [
            'created_by' => 'user-123',
            'created_at' => $createdAt->format(DateTimeImmutable::ATOM),
            'updated_by' => null,
            'updated_at' => null,
        ];
        
        $metadata = AuditMetadata::fromArray($data);
        
        $this->assertSame('user-123', $metadata->getCreatedBy());
        $this->assertEquals($createdAt, $metadata->getCreatedAt());
        $this->assertNull($metadata->getUpdatedBy());
        $this->assertNull($metadata->getUpdatedAt());
    }

    public function test_from_array_with_update(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2024-01-02 15:30:00');
        $data = [
            'created_by' => 'user-123',
            'created_at' => $createdAt->format(DateTimeImmutable::ATOM),
            'updated_by' => 'user-456',
            'updated_at' => $updatedAt->format(DateTimeImmutable::ATOM),
        ];
        
        $metadata = AuditMetadata::fromArray($data);
        
        $this->assertSame('user-123', $metadata->getCreatedBy());
        $this->assertSame('user-456', $metadata->getUpdatedBy());
        $this->assertEquals($createdAt, $metadata->getCreatedAt());
        $this->assertEquals($updatedAt, $metadata->getUpdatedAt());
    }
}
