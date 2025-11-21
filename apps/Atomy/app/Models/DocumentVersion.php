<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Document\Contracts\DocumentVersionInterface;

/**
 * Document Version Eloquent model.
 *
 * Implements DocumentVersionInterface from Nexus\Document package.
 */
class DocumentVersion extends Model implements DocumentVersionInterface
{
    protected $fillable = [
        'id',
        'document_id',
        'version_number',
        'storage_path',
        'change_description',
        'created_by',
        'checksum',
        'file_size',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    // DocumentVersionInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getDocumentId(): string
    {
        return $this->document_id;
    }

    public function getVersionNumber(): int
    {
        return $this->version_number;
    }

    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    public function getChangeDescription(): ?string
    {
        return $this->change_description;
    }

    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getFileSize(): int
    {
        return $this->file_size;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }
}
