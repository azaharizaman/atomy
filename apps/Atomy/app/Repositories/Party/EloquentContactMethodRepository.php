<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyContactMethod;
use Nexus\Party\Contracts\ContactMethodInterface;
use Nexus\Party\Contracts\ContactMethodRepositoryInterface;
use Nexus\Party\Enums\ContactMethodType;
use Nexus\Party\Exceptions\ContactMethodNotFoundException;

final readonly class EloquentContactMethodRepository implements ContactMethodRepositoryInterface
{
    public function findById(string $id): ?ContactMethodInterface
    {
        return PartyContactMethod::find($id);
    }

    public function getByPartyId(string $partyId): array
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getByType(string $partyId, ContactMethodType $type): array
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->where('type', $type->value)
            ->orderBy('is_primary', 'desc')
            ->get()
            ->all();
    }

    public function getPrimaryContactMethod(string $partyId, ContactMethodType $type): ?ContactMethodInterface
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->where('type', $type->value)
            ->where('is_primary', true)
            ->first();
    }

    public function findByValue(string $tenantId, ContactMethodType $type, string $value): ?ContactMethodInterface
    {
        return PartyContactMethod::whereHas('party', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->where('type', $type->value)
            ->where('value', $value)
            ->first();
    }

    public function save(array $data): ContactMethodInterface
    {
        $contactMethod = new PartyContactMethod($data);
        $contactMethod->save();
        return $contactMethod;
    }

    public function update(string $id, array $data): ContactMethodInterface
    {
        $contactMethod = PartyContactMethod::find($id);
        
        if (!$contactMethod) {
            throw ContactMethodNotFoundException::forId($id);
        }
        
        $contactMethod->fill($data);
        $contactMethod->save();
        return $contactMethod;
    }

    public function delete(string $id): bool
    {
        $contactMethod = PartyContactMethod::find($id);
        
        if (!$contactMethod) {
            return false;
        }
        
        return (bool) $contactMethod->delete();
    }

    public function clearPrimaryFlag(string $partyId, ContactMethodType $type): void
    {
        PartyContactMethod::where('party_id', $partyId)
            ->where('type', $type->value)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
