<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Office;
use Nexus\Backoffice\Contracts\OfficeInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;

class DbOfficeRepository implements OfficeRepositoryInterface
{
    public function findById(string $id): ?OfficeInterface { return Office::find($id); }
    public function findByCode(string $companyId, string $code): ?OfficeInterface { return Office::where('company_id', $companyId)->where('code', $code)->first(); }
    public function getByCompany(string $companyId): array { return Office::where('company_id', $companyId)->get()->all(); }
    public function getActiveByCompany(string $companyId): array { return Office::where('company_id', $companyId)->whereIn('status', ['active', 'temporary'])->get()->all(); }
    public function getByLocation(string $country, ?string $city = null): array { $query = Office::where('country', $country); if ($city) $query->where('city', $city); return $query->get()->all(); }
    public function save(array $data): OfficeInterface { return Office::create($data); }
    public function update(string $id, array $data): OfficeInterface { $office = Office::findOrFail($id); $office->update($data); return $office->fresh(); }
    public function delete(string $id): bool { return Office::destroy($id) > 0; }
    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool { $query = Office::where('company_id', $companyId)->where('code', $code); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function hasActiveStaff(string $officeId): bool { /* TODO */ return false; }
    public function getHeadOffice(string $companyId): ?OfficeInterface { return Office::where('company_id', $companyId)->where('type', 'head_office')->first(); }
    public function hasHeadOffice(string $companyId, ?string $excludeId = null): bool { $query = Office::where('company_id', $companyId)->where('type', 'head_office'); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
}
