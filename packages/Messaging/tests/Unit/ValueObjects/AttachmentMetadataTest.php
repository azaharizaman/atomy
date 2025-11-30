<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\ValueObjects;

use Nexus\Messaging\ValueObjects\AttachmentMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\ValueObjects\AttachmentMetadata
 */
final class AttachmentMetadataTest extends TestCase
{
    public function test_can_create_attachment_metadata(): void
    {
        $attachment = new AttachmentMetadata(
            filename: 'invoice.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024000,
            storageReference: 's3://bucket/invoice.pdf',
            url: 'https://example.com/invoice.pdf'
        );

        $this->assertSame('invoice.pdf', $attachment->filename);
        $this->assertSame('application/pdf', $attachment->mimeType);
        $this->assertSame(1024000, $attachment->sizeBytes);
        $this->assertSame('s3://bucket/invoice.pdf', $attachment->storageReference);
        $this->assertSame('https://example.com/invoice.pdf', $attachment->url);
    }

    public function test_throws_exception_for_empty_filename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename cannot be empty');

        new AttachmentMetadata(
            filename: '',
            mimeType: 'application/pdf',
            sizeBytes: 1024
        );
    }

    public function test_throws_exception_for_negative_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size cannot be negative');

        new AttachmentMetadata(
            filename: 'file.pdf',
            mimeType: 'application/pdf',
            sizeBytes: -100
        );
    }

    public function test_from_array(): void
    {
        $data = [
            'filename' => 'report.xlsx',
            'mime_type' => 'application/vnd.ms-excel',
            'size_bytes' => 2048000,
            'storage_reference' => 'doc-123',
            'url' => 'https://cdn.example.com/report.xlsx',
        ];

        $attachment = AttachmentMetadata::fromArray($data);

        $this->assertSame('report.xlsx', $attachment->filename);
        $this->assertSame('application/vnd.ms-excel', $attachment->mimeType);
        $this->assertSame(2048000, $attachment->sizeBytes);
        $this->assertSame('doc-123', $attachment->storageReference);
        $this->assertSame('https://cdn.example.com/report.xlsx', $attachment->url);
    }

    public function test_to_array(): void
    {
        $attachment = new AttachmentMetadata(
            filename: 'image.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 512000
        );

        $array = $attachment->toArray();

        $this->assertSame([
            'filename' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 512000,
            'storage_reference' => null,
            'url' => null,
        ], $array);
    }

    public function test_human_readable_size(): void
    {
        $attachment1 = new AttachmentMetadata('file', 'text/plain', 512);
        $this->assertSame('512 B', $attachment1->getHumanReadableSize());

        $attachment2 = new AttachmentMetadata('file', 'text/plain', 1024);
        $this->assertSame('1 KB', $attachment2->getHumanReadableSize());

        $attachment3 = new AttachmentMetadata('file', 'text/plain', 1048576);
        $this->assertSame('1 MB', $attachment3->getHumanReadableSize());

        $attachment4 = new AttachmentMetadata('file', 'text/plain', 1073741824);
        $this->assertSame('1 GB', $attachment4->getHumanReadableSize());
    }
}
