<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\ValueObjects\RelationshipType;

/**
 * Document Relationship Eloquent model.
 *
 * Implements DocumentRelationshipInterface from Nexus\Document package.
 */
class DocumentRelationship extends Model implements DocumentRelationshipInterface
{
    protected $fillable = [
        'id',
        'source_document_id',
        'target_document_id',
        'relationship_type',
        'created_by',
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function targetDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'target_document_id');
    }

    // DocumentRelationshipInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceDocumentId(): string
    {
        return $this->source_document_id;
    }

    public function getTargetDocumentId(): string
    {
        return $this->target_document_id;
    }

    public function getRelationshipType(): RelationshipType
    {
        return $this->relationship_type;
    }

    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }
}
