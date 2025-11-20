<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Party\Contracts\ContactMethodInterface;
use Nexus\Party\Enums\ContactMethodType;

/**
 * Party Contact Method Eloquent model.
 *
 * @property string $id
 * @property string $party_id
 * @property string $type
 * @property string $value
 * @property bool $is_primary
 * @property bool $is_verified
 * @property \DateTimeInterface|null $verified_at
 * @property array $metadata
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class PartyContactMethod extends Model implements ContactMethodInterface
{
    use HasUlids;

    protected $table = 'party_contact_methods';

    protected $fillable = [
        'party_id',
        'type',
        'value',
        'is_primary',
        'is_verified',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    // ContactMethodInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getPartyId(): string
    {
        return $this->party_id;
    }

    public function getType(): ContactMethodType
    {
        return ContactMethodType::from($this->type);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function getVerifiedAt(): ?\DateTimeInterface
    {
        return $this->verified_at;
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
