<?php

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Unit;
use Nexus\Backoffice\Contracts\UnitInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentUnitRepository implements UnitRepositoryInterface
{
    public function save(array $data): UnitInterface
    {
        return Unit::create($data);
    }

    public function update(string $id, array $data): UnitInterface
    {
        $unit = Unit::findOrFail($id);
        $unit->update($data);
        return $unit;
    }

    public function delete(string $id): bool
    {
        return (bool) Unit::destroy($id);
    }

    public function findById(string $id): ?UnitInterface
    {
        return Unit::find($id);
    }

    public function findByCode(string $companyId, string $code): ?UnitInterface
    {
        return Unit::where('company_id', $companyId)
            ->where('code', $code)
            ->first();
    }

    public function getByCompany(string $companyId): array
    {
        return Unit::where('company_id', $companyId)->get()->all();
    }

    public function getActiveByCompany(string $companyId): array
    {
        return Unit::where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>', now());
            })
            ->get()
            ->all();
    }

    public function getByType(string $companyId, string $type): array
    {
        return Unit::where('company_id', $companyId)
            ->where('type', $type)
            ->get()
            ->all();
    }

    public function getUnitMembers(string $unitId): array
    {
        $unit = Unit::findOrFail($unitId);
        return $unit->members()->pluck('staff_id')->all();
    }

    public function addMember(string $unitId, string $staffId, string $role): void
    {
        $unit = Unit::findOrFail($unitId);
        $unit->members()->attach($staffId, ['role' => $role]);
    }

    public function removeMember(string $unitId, string $staffId): void
    {
        $unit = Unit::findOrFail($unitId);
        $unit->members()->detach($staffId);
    }

    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool
    {
        $query = Unit::where('company_id', $companyId)->where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function isMember(string $unitId, string $staffId): bool
    {
        $unit = Unit::findOrFail($unitId);
        return $unit->members()->where('staff_id', $staffId)->exists();
    }

    public function getMemberRole(string $unitId, string $staffId): ?string
    {
        $unit = Unit::findOrFail($unitId);
        $member = $unit->members()->where('staff_id', $staffId)->first();
        return $member ? $member->pivot->role : null;
    }
}
