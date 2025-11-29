<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Office;
use Nexus\Backoffice\Contracts\OfficeInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;

class EloquentOfficeRepository implements OfficeRepositoryInterface
{
    public function findById(string $id): ?OfficeInterface
    {
        return Office::find($id);
    }

    public function findByCode(string $companyId, string $code): ?OfficeInterface
    {
        return Office::where('company_id', $companyId)
            ->where('code', $code)
            ->first();
    }

    public function getByCompany(string $companyId): array
    {
        return Office::where('company_id', $companyId)->get()->all();
    }

    public function getByLocation(string $country, ?string $city = null): array
    {
        $query = Office::where('country', $country);
        if ($city) {
            $query->where('city', $city);
        }
        return $query->get()->all();
    }

    public function getAll(): array
    {
        return Office::with('company')->get()->all();
    }

    public function getActiveByCompany(string $companyId): array
    {
        return Office::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    public function getHeadOffice(string $companyId): ?OfficeInterface
    {
        return Office::where('company_id', $companyId)
            ->where('is_head_office', true)
            ->first();
    }

    public function save(array $data): OfficeInterface
    {
        return Office::create($data);
    }

    public function update(string $id, array $data): OfficeInterface
    {
        $office = Office::findOrFail($id);
        $office->update($data);
        return $office;
    }

    public function delete(string $id): bool
    {
        $office = Office::find($id);
        if ($office) {
            return $office->delete();
        }
        return false;
    }

    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool
    {
        $query = Office::where('company_id', $companyId)
            ->where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function hasActiveStaff(string $officeId): bool
    {
        // Assuming Staff model has office_id or relationship
        // Based on StaffInterface, it likely belongs to an office.
        // Let's check Staff model or interface if needed, but usually it's a relationship.
        // Wait, Staff model has 'office_id'.
        return \App\Models\Backoffice\Staff::where('office_id', $officeId)
            ->where('is_active', true)
            ->exists();
    }

    public function hasHeadOffice(string $companyId, ?string $excludeId = null): bool
    {
        $query = Office::where('company_id', $companyId)
            ->where('is_head_office', true);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
