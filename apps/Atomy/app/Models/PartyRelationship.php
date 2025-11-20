<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Party\Contracts\PartyRelationshipInterface;
use Nexus\Party\Enums\RelationshipType;

/**
 * Party Relationship Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $from_party_id
 * @property string $to_party_id
 * @property string $relationship_type
 * @property \DateTimeInterface $effective_from
 * @property \DateTimeInterface|null $effective_to
 * @property string|null $role
 * @property array $metadata
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class PartyRelationship extends Model implements PartyRelationshipInterface
{
    use HasUlids;

    protected $table = 'party_relationships';

    protected $fillable = [
        'tenant_id',
        'from_party_id',
        'to_party_id',
        'relationship_type',
        'effective_from',
        'effective_to',
        'role',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function fromParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'from_party_id');
    }

    public function toParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'to_party_id');
    }

    // PartyRelationshipInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getFromPartyId(): string
    {
        return $this->from_party_id;
    }

    public function getToPartyId(): string
    {
        return $this->to_party_id;
    }

    public function getRelationshipType(): RelationshipType
    {
        return RelationshipType::from($this->relationship_type);
    }

    public function getEffectiveFrom(): \DateTimeInterface
    {
        return $this->effective_from;
    }

    public function getEffectiveTo(): ?\DateTimeInterface
    {
        return $this->effective_to;
    }

    public function isActive(?\DateTimeInterface $asOf = null): bool
    {
        $asOf = $asOf ?? new \DateTimeImmutable();
        
        if ($asOf < $this->effective_from) {
            return false;
        }

        if ($this->effective_to && $asOf >= $this->effective_to) {
            return false;
        }

        return true;
    }

    public function getRole(): ?string
    {
        return $this->role;
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
}
