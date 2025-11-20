<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\Party;
use Nexus\Party\Contracts\PartyInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Enums\PartyType;
use Nexus\Party\Exceptions\PartyNotFoundException;
use Nexus\Party\ValueObjects\TaxIdentity;

final readonly class EloquentPartyRepository implements PartyRepositoryInterface
{
    public function findById(string $id): ?PartyInterface
    {
        return Party::find($id);
    }

    public function findByLegalName(string $tenantId, string $legalName): ?PartyInterface
    {
        return Party::where('tenant_id', $tenantId)
            ->where('legal_name', $legalName)
            ->first();
    }

    public function findByTaxIdentity(string $tenantId, string $country, string $taxNumber): ?PartyInterface
    {
        return Party::where('tenant_id', $tenantId)
            ->whereJsonContains('tax_identity->country', $country)
            ->whereJsonContains('tax_identity->number', $taxNumber)
            ->first();
    }

    public function searchByName(string $tenantId, string $searchTerm, int $limit = 50): array
    {
        return Party::where('tenant_id', $tenantId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('legal_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('display_name', 'LIKE', "%{$searchTerm}%");
            })
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getAll(string $tenantId, array $filters = []): array
    {
        $query = Party::where('tenant_id', $tenantId);
        
        if (isset($filters['party_type'])) {
            $query->where('party_type', $filters['party_type']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('legal_name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('display_name', 'LIKE', "%{$filters['search']}%");
            });
        }
        
        return $query->limit($filters['limit'] ?? 1000)->get()->all();
    }

    public function getByType(string $tenantId, PartyType $type): array
    {
        return Party::where('tenant_id', $tenantId)
            ->where('party_type', $type->value)
            ->get()
            ->all();
    }

    public function save(array $data): PartyInterface
    {
        $party = Party::create($data);
        return $party;
    }

    public function update(string $id, array $data): PartyInterface
    {
        $party = Party::find($id);
        
        if (!$party) {
            throw PartyNotFoundException::forId($id);
        }
        
        $party->fill($data);
        $party->save();
        return $party;
    }

    public function delete(string $id): bool
    {
        $party = Party::find($id);
        
        if (!$party) {
            return false;
        }
        
        return (bool) $party->delete();
    }

    public function findPotentialDuplicates(
        string $tenantId,
        ?string $legalName = null,
        ?TaxIdentity $taxIdentity = null
    ): array {
        $query = Party::where('tenant_id', $tenantId);

        if ($legalName !== null) {
            $query->where('legal_name', 'LIKE', "%{$legalName}%");
        }

        if ($taxIdentity !== null) {
            $query->orWhere(function ($q) use ($taxIdentity) {
                $q->whereJsonContains('tax_identity->country', $taxIdentity->country)
                  ->whereJsonContains('tax_identity->number', $taxIdentity->number);
            });
        }

        return $query->limit(10)->get()->all();
    }

    public function getAllByTenant(string $tenantId, int $limit = 1000): array
    {
        return Party::where('tenant_id', $tenantId)
            ->limit($limit)
            ->get()
            ->all();
    }
}
