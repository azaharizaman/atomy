<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Document\ValueObjects\DocumentType;
use Nexus\Tenant\Services\TenantManager;

/**
 * Document Eloquent model.
 *
 * Implements DocumentInterface from Nexus\Document package.
 */
class Document extends Model implements DocumentInterface
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'tenant_id',
        'owner_id',
        'type',
        'state',
        'storage_path',
        'checksum',
        'mime_type',
        'file_size',
        'original_filename',
        'version',
        'metadata',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'state' => DocumentState::class,
        'metadata' => 'array',
        'file_size' => 'integer',
        'version' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        // Apply tenant scoping
        static::creating(function (Document $document) {
            if (empty($document->tenant_id)) {
                $document->tenant_id = app(TenantManager::class)->getCurrentTenantId();
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound(TenantManager::class)) {
                $tenantId = app(TenantManager::class)->getCurrentTenantId();
                $builder->where('tenant_id', $tenantId);
            }
        });
    }

    // Relationships

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function sourceRelationships(): HasMany
    {
        return $this->hasMany(DocumentRelationship::class, 'source_document_id');
    }

    public function targetRelationships(): HasMany
    {
        return $this->hasMany(DocumentRelationship::class, 'target_document_id');
    }

    // DocumentInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getOwnerId(): string
    {
        return $this->owner_id;
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getState(): DocumentState
    {
        return $this->state;
    }

    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getFileSize(): int
    {
        return $this->file_size;
    }

    public function getOriginalFilename(): string
    {
        return $this->original_filename;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    // Additional helper methods for package services

    public function setState(DocumentState $state): void
    {
        $this->state = $state;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function setStoragePath(string $path): void
    {
        $this->storage_path = $path;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }
}
