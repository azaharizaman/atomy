<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Party\Contracts\AddressInterface;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\ValueObjects\PostalAddress;

/**
 * Party Address Eloquent model.
 *
 * @property string $id
 * @property string $party_id
 * @property string $address_type
 * @property array $postal_address
 * @property bool $is_primary
 * @property \DateTimeInterface|null $effective_from
 * @property \DateTimeInterface|null $effective_to
 * @property array $metadata
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class PartyAddress extends Model implements AddressInterface
{
    use HasUlids;

    protected $table = 'party_addresses';

    protected $fillable = [
        'party_id',
        'address_type',
        'postal_address',
        'is_primary',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'postal_address' => 'array',
        'is_primary' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    // AddressInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getPartyId(): string
    {
        return $this->party_id;
    }

    public function getAddressType(): AddressType
    {
        return AddressType::from($this->address_type);
    }

    public function getPostalAddress(): PostalAddress
    {
        $data = $this->postal_address;

        return new PostalAddress(
            streetLine1: $data['street_line_1'],
            city: $data['city'],
            postalCode: $data['postal_code'],
            country: $data['country'],
            streetLine2: $data['street_line_2'] ?? null,
            streetLine3: $data['street_line_3'] ?? null,
            state: $data['state'] ?? null,
            district: $data['district'] ?? null
        );
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function getEffectiveFrom(): ?\DateTimeInterface
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
        
        if ($this->effective_from && $asOf < $this->effective_from) {
            return false;
        }

        if ($this->effective_to && $asOf >= $this->effective_to) {
            return false;
        }

        return true;
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
