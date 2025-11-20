<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyRelationship;
use Illuminate\Support\Facades\DB;
use Nexus\Party\Contracts\PartyRelationshipInterface;
use Nexus\Party\Contracts\PartyRelationshipRepositoryInterface;
use Nexus\Party\Enums\RelationshipType;
use Nexus\Party\Exceptions\RelationshipNotFoundException;

final readonly class EloquentPartyRelationshipRepository implements PartyRelationshipRepositoryInterface
{
    public function findById(string $id): ?PartyRelationshipInterface
    {
        return PartyRelationship::find($id);
    }

    public function getRelationshipsFrom(string $partyId, ?RelationshipType $type = null): array
    {
        $query = PartyRelationship::where('from_party_id', $partyId);
        
        if ($type !== null) {
            $query->where('relationship_type', $type->value);
        }
        
        return $query->get()->all();
    }

    public function getRelationshipsTo(string $partyId, ?RelationshipType $type = null): array
    {
        $query = PartyRelationship::where('to_party_id', $partyId);
        
        if ($type !== null) {
            $query->where('relationship_type', $type->value);
        }
        
        return $query->get()->all();
    }

    public function getActiveRelationships(string $partyId, ?\DateTimeInterface $asOf = null): array
    {
        $asOf = $asOf ?? new \DateTimeImmutable();
        $date = $asOf->format('Y-m-d');

        return PartyRelationship::where(function ($query) use ($partyId) {
                $query->where('from_party_id', $partyId)
                      ->orWhere('to_party_id', $partyId);
            })
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

    public function getCurrentEmployer(string $individualPartyId, ?\DateTimeInterface $asOf = null): ?PartyRelationshipInterface
    {
        $asOf = $asOf ?? new \DateTimeImmutable();
        $date = $asOf->format('Y-m-d');

        return PartyRelationship::where('from_party_id', $individualPartyId)
            ->where('relationship_type', RelationshipType::EMPLOYMENT_AT->value)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
            })
            ->first();
    }

    public function getParentOrganization(string $subsidiaryPartyId): ?PartyRelationshipInterface
    {
        return PartyRelationship::where('from_party_id', $subsidiaryPartyId)
            ->where('relationship_type', RelationshipType::SUBSIDIARY_OF->value)
            ->first();
    }

    public function getSubsidiaries(string $parentPartyId): array
    {
        return PartyRelationship::where('to_party_id', $parentPartyId)
            ->where('relationship_type', RelationshipType::SUBSIDIARY_OF->value)
            ->get()
            ->all();
    }

    public function getOrganizationalChain(string $partyId, int $maxDepth = 50): array
    {
        // Use recursive CTE to get the complete organizational hierarchy
        $results = DB::select("
            WITH RECURSIVE org_chain AS (
                -- Base case: start with the given party
                SELECT 
                    id,
                    from_party_id,
                    to_party_id,
                    relationship_type,
                    effective_from,
                    effective_to,
                    1 as depth
                FROM party_relationships
                WHERE from_party_id = :party_id
                  AND relationship_type = :rel_type
                  AND effective_from <= CURRENT_DATE
                  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
                
                UNION ALL
                
                -- Recursive case: get parent relationships
                SELECT 
                    pr.id,
                    pr.from_party_id,
                    pr.to_party_id,
                    pr.relationship_type,
                    pr.effective_from,
                    pr.effective_to,
                    oc.depth + 1
                FROM party_relationships pr
                INNER JOIN org_chain oc ON pr.from_party_id = oc.to_party_id
                WHERE pr.relationship_type = :rel_type
                  AND pr.effective_from <= CURRENT_DATE
                  AND (pr.effective_to IS NULL OR pr.effective_to >= CURRENT_DATE)
                  AND oc.depth < :max_depth
            )
            SELECT * FROM org_chain ORDER BY depth
        ", [
            'party_id' => $partyId,
            'rel_type' => RelationshipType::SUBSIDIARY_OF->value,
            'max_depth' => $maxDepth,
        ]);

        // Convert stdClass results to PartyRelationship models
        return array_map(function ($row) {
            return PartyRelationship::find($row->id);
        }, $results);
    }

    public function save(array $data): PartyRelationshipInterface
    {
        $relationship = new PartyRelationship($data);
        $relationship->save();
        return $relationship;
    }

    public function update(string $id, array $data): PartyRelationshipInterface
    {
        $relationship = PartyRelationship::find($id);
        
        if (!$relationship) {
            throw RelationshipNotFoundException::forId($id);
        }
        
        $relationship->fill($data);
        $relationship->save();
        return $relationship;
    }

    public function delete(string $id): bool
    {
        $relationship = PartyRelationship::find($id);
        
        if (!$relationship) {
            return false;
        }
        
        return (bool) $relationship->delete();
    }
}
