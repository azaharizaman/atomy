<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BackofficeUnit;
use Nexus\Backoffice\Contracts\UnitInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;

class DbUnitRepository implements UnitRepositoryInterface
{
    public function findById(string $id): ?UnitInterface { return BackofficeUnit::find($id); }
    public function findByCode(string $companyId, string $code): ?UnitInterface { return BackofficeUnit::where('company_id', $companyId)->where('code', $code)->first(); }
    public function getByCompany(string $companyId): array { return BackofficeUnit::where('company_id', $companyId)->get()->all(); }
    public function getActiveByCompany(string $companyId): array { return BackofficeUnit::where('company_id', $companyId)->where('status', 'active')->get()->all(); }
    public function getByType(string $companyId, string $type): array { return BackofficeUnit::where('company_id', $companyId)->where('type', $type)->get()->all(); }
    public function getUnitMembers(string $unitId): array { /* TODO */ return []; }
    public function save(array $data): UnitInterface { return BackofficeUnit::create($data); }
    public function update(string $id, array $data): UnitInterface { $unit = BackofficeUnit::findOrFail($id); $unit->update($data); return $unit->fresh(); }
    public function delete(string $id): bool { return BackofficeUnit::destroy($id) > 0; }
    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool { $query = BackofficeUnit::where('company_id', $companyId)->where('code', $code); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function addMember(string $unitId, string $staffId, string $role): void { /* TODO */ }
    public function removeMember(string $unitId, string $staffId): void { /* TODO */ }
    public function isMember(string $unitId, string $staffId): bool { /* TODO */ return false; }
    public function getMemberRole(string $unitId, string $staffId): ?string { /* TODO */ return null; }
}
