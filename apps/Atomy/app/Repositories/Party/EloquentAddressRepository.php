<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyAddress;
use Nexus\Party\Contracts\AddressInterface;
use Nexus\Party\Contracts\AddressRepositoryInterface;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\Exceptions\AddressNotFoundException;

final readonly class EloquentAddressRepository implements AddressRepositoryInterface
{
    public function findById(string $id): ?AddressInterface
    {
        return PartyAddress::find($id);
    }

    public function getByPartyId(string $partyId): array
    {
        return PartyAddress::where('party_id', $partyId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getPrimaryAddress(string $partyId, ?AddressType $type = null): ?AddressInterface
    {
        $query = PartyAddress::where('party_id', $partyId)
            ->where('is_primary', true);
            
        if ($type !== null) {
            $query->where('address_type', $type->value);
        }
        
        return $query->first();
    }

    public function getActiveAddresses(string $partyId, ?\DateTimeInterface $asOf = null): array
    {
        $asOf = $asOf ?? new \DateTimeImmutable();
        $date = $asOf->format('Y-m-d');
        
        return PartyAddress::where('party_id', $partyId)
            ->where(function ($query) use ($date) {
                $query->where('effective_from', '<=', $date)
                      ->where(function ($q) use ($date) {
                          $q->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $date);
                      });
            })
            ->get()
            ->all();
    }

    public function save(array $data): AddressInterface
    {
        $address = new PartyAddress($data);
        $address->save();
        return $address;
    }

    public function update(string $id, array $data): AddressInterface
    {
        $address = PartyAddress::find($id);
        
        if (!$address) {
            throw AddressNotFoundException::forId($id);
        }
        
        $address->fill($data);
        $address->save();
        return $address;
    }

    public function delete(string $id): bool
    {
        $address = PartyAddress::find($id);
        
        if (!$address) {
            return false;
        }
        
        return (bool) $address->delete();
    }

    public function clearPrimaryFlag(string $partyId, AddressType $type): void
    {
        PartyAddress::where('party_id', $partyId)
            ->where('address_type', $type->value)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
