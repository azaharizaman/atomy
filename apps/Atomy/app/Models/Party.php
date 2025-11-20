<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Party\Contracts\PartyInterface;
use Nexus\Party\Enums\PartyType;
use Nexus\Party\ValueObjects\TaxIdentity;

/**
 * Party Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $party_type
 * @property string $legal_name
 * @property string|null $trading_name
 * @property array|null $tax_identity
 * @property \DateTimeInterface|null $date_of_birth
 * @property \DateTimeInterface|null $registration_date
 * @property array $metadata
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class Party extends Model implements PartyInterface
{
    use HasUlids;

    protected $table = 'parties';

    protected $fillable = [
        'tenant_id',
        'party_type',
        'legal_name',
        'trading_name',
        'tax_identity',
        'date_of_birth',
        'registration_date',
        'metadata',
    ];

    protected $casts = [
        'tax_identity' => 'array',
        'date_of_birth' => 'date',
        'registration_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class, 'party_id');
    }

    public function contactMethods(): HasMany
    {
        return $this->hasMany(PartyContactMethod::class, 'party_id');
    }

    public function relationshipsFrom(): HasMany
    {
        return $this->hasMany(PartyRelationship::class, 'from_party_id');
    }

    public function relationshipsTo(): HasMany
    {
        return $this->hasMany(PartyRelationship::class, 'to_party_id');
    }

    // PartyInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getPartyType(): PartyType
    {
        return PartyType::from($this->party_type);
    }

    public function getLegalName(): string
    {
        return $this->legal_name;
    }

    public function getTradingName(): ?string
    {
        return $this->trading_name;
    }

    public function getTaxIdentity(): ?TaxIdentity
    {
        if (!$this->tax_identity) {
            return null;
        }

        return new TaxIdentity(
            country: $this->tax_identity['country'],
            number: $this->tax_identity['number'],
            issueDate: isset($this->tax_identity['issue_date']) 
                ? new \DateTimeImmutable($this->tax_identity['issue_date']) 
                : null,
            expiryDate: isset($this->tax_identity['expiry_date']) 
                ? new \DateTimeImmutable($this->tax_identity['expiry_date']) 
                : null,
            type: $this->tax_identity['type'] ?? null
        );
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
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

    public function isIndividual(): bool
    {
        return $this->getPartyType()->isIndividual();
    }

    public function isOrganization(): bool
    {
        return $this->getPartyType()->isOrganization();
    }
}
