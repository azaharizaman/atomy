<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Common\ValueObjects\AttachmentMetadata;
use Nexus\Common\Exceptions\InvalidValueException;

final class AttachmentMetadataTest extends TestCase
{
    public function test_creates_metadata(): void
    {
        $uploadedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        
        $metadata = new AttachmentMetadata(
            fileName: 'invoice.pdf',
            mimeType: 'application/pdf',
            sizeInBytes: 1024000,
            uploadedAt: $uploadedAt,
            uploadedBy: 'user-123'
        );
        
        $this->assertSame('invoice.pdf', $metadata->getFileName());
        $this->assertSame('application/pdf', $metadata->getMimeType());
        $this->assertSame(1024000, $metadata->getSizeInBytes());
        $this->assertSame($uploadedAt, $metadata->getUploadedAt());
        $this->assertSame('user-123', $metadata->getUploadedBy());
        $this->assertNull($metadata->getStoragePath());
    }

    public function test_creates_metadata_with_storage_path(): void
    {
        $metadata = new AttachmentMetadata(
            fileName: 'test.pdf',
            mimeType: 'application/pdf',
            sizeInBytes: 1000,
            uploadedAt: new \DateTimeImmutable(),
            uploadedBy: 'user-456',
            storagePath: 's3://bucket/path/file.pdf'
        );
        
        $this->assertSame('s3://bucket/path/file.pdf', $metadata->getStoragePath());
    }

    public function test_throws_exception_for_negative_size(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('File size cannot be negative');
        
        new AttachmentMetadata(
            fileName: 'test.pdf',
            mimeType: 'application/pdf',
            sizeInBytes: -100,
            uploadedAt: new \DateTimeImmutable(),
            uploadedBy: 'user-123'
        );
    }

    public function test_throws_exception_for_empty_filename(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('File name is required');
        
        new AttachmentMetadata(
            fileName: '',
            mimeType: 'application/pdf',
            sizeInBytes: 1000,
            uploadedAt: new \DateTimeImmutable(),
            uploadedBy: 'user-123'
        );
    }

    public function test_throws_exception_for_empty_mime_type(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('MIME type is required');
        
        new AttachmentMetadata(
            fileName: 'test.pdf',
            mimeType: '',
            sizeInBytes: 1000,
            uploadedAt: new \DateTimeImmutable(),
            uploadedBy: 'user-123'
        );
    }

    public function test_get_file_extension(): void
    {
        $uploadedAt = new \DateTimeImmutable();
        
        $metadata = new AttachmentMetadata('document.pdf', 'application/pdf', 1000, $uploadedAt, 'user-1');
        $this->assertSame('pdf', $metadata->getFileExtension());
        
        $metadata2 = new AttachmentMetadata('image.jpeg', 'image/jpeg', 2000, $uploadedAt, 'user-1');
        $this->assertSame('jpeg', $metadata2->getFileExtension());
        
        $metadata3 = new AttachmentMetadata('noextension', 'text/plain', 500, $uploadedAt, 'user-1');
        $this->assertSame('', $metadata3->getFileExtension());
    }

    public function test_get_human_readable_size(): void
    {
        $uploadedAt = new \DateTimeImmutable();
        $userId = 'user-1';
        
        $metadata1 = new AttachmentMetadata('file.txt', 'text/plain', 500, $uploadedAt, $userId);
        $this->assertSame('500 B', $metadata1->getHumanReadableSize());
        
        $metadata2 = new AttachmentMetadata('file.txt', 'text/plain', 1024, $uploadedAt, $userId);
        $this->assertSame('1.00 KB', $metadata2->getHumanReadableSize());
        
        $metadata3 = new AttachmentMetadata('file.txt', 'text/plain', 1048576, $uploadedAt, $userId);
        $this->assertSame('1.00 MB', $metadata3->getHumanReadableSize());
        
        $metadata4 = new AttachmentMetadata('file.txt', 'text/plain', 1073741824, $uploadedAt, $userId);
        $this->assertSame('1.00 GB', $metadata4->getHumanReadableSize());
    }

    public function test_to_array(): void
    {
        $uploadedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        
        $metadata = new AttachmentMetadata('test.pdf', 'application/pdf', 2048, $uploadedAt, 'user-123');
        $array = $metadata->toArray();
        
        $this->assertArrayHasKey('file_name', $array);
        $this->assertArrayHasKey('mime_type', $array);
        $this->assertArrayHasKey('size_in_bytes', $array);
        $this->assertArrayHasKey('uploaded_at', $array);
        $this->assertArrayHasKey('uploaded_by', $array);
        $this->assertSame('test.pdf', $array['file_name']);
        $this->assertSame(2048, $array['size_in_bytes']);
        $this->assertSame('user-123', $array['uploaded_by']);
    }

    public function test_from_array(): void
    {
        $data = [
            'file_name' => 'document.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_in_bytes' => 5120,
            'uploaded_at' => '2024-01-15 10:30:00',
            'uploaded_by' => 'user-789',
            'storage_path' => '/uploads/document.docx'
        ];
        
        $metadata = AttachmentMetadata::fromArray($data);
        
        $this->assertSame('document.docx', $metadata->getFileName());
        $this->assertSame(5120, $metadata->getSizeInBytes());
        $this->assertSame('user-789', $metadata->getUploadedBy());
        $this->assertSame('/uploads/document.docx', $metadata->getStoragePath());
    }

    public function test_to_string(): void
    {
        $uploadedAt = new \DateTimeImmutable();
        
        $metadata = new AttachmentMetadata('report.pdf', 'application/pdf', 1024, $uploadedAt, 'user-1');
        $string = $metadata->toString();
        
        $this->assertStringContainsString('report.pdf', $string);
        $this->assertStringContainsString('1.00 KB', $string);
    }
}

