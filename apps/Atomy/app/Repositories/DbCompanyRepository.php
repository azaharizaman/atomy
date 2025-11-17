<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class DbCompanyRepository implements CompanyRepositoryInterface
{
    public function findById(string $id): ?CompanyInterface
    {
        return Company::find($id);
    }

    public function findByCode(string $code): ?CompanyInterface
    {
        return Company::where('code', $code)->first();
    }

    public function findByRegistrationNumber(string $registrationNumber): ?CompanyInterface
    {
        return Company::where('registration_number', $registrationNumber)->first();
    }

    public function getAll(): array
    {
        return Company::all()->all();
    }

    public function getActive(): array
    {
        return Company::where('status', 'active')->get()->all();
    }

    public function getSubsidiaries(string $parentCompanyId): array
    {
        return Company::where('parent_company_id', $parentCompanyId)->get()->all();
    }

    public function getParentChain(string $companyId): array
    {
        $chain = [];
        $currentId = $companyId;
        $maxDepth = 50; // Prevent infinite loop
        $depth = 0;

        while ($currentId && $depth < $maxDepth) {
            $company = Company::find($currentId);
            if (!$company) {
                break;
            }

            $chain[] = $company;
            $currentId = $company->parent_company_id;
            $depth++;
        }

        return $chain;
    }

    public function save(array $data): CompanyInterface
    {
        return Company::create($data);
    }

    public function update(string $id, array $data): CompanyInterface
    {
        $company = Company::findOrFail($id);
        $company->update($data);
        return $company->fresh();
    }

    public function delete(string $id): bool
    {
        return Company::destroy($id) > 0;
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = Company::where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function registrationNumberExists(string $registrationNumber, ?string $excludeId = null): bool
    {
        $query = Company::where('registration_number', $registrationNumber);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function hasCircularReference(string $companyId, string $proposedParentId): bool
    {
        // Walk up the parent chain from proposed parent
        $currentId = $proposedParentId;
        $maxDepth = 50; // Prevent infinite loop
        $depth = 0;

        while ($currentId && $depth < $maxDepth) {
            // If we encounter the company being updated, it's circular
            if ($currentId === $companyId) {
                return true;
            }

            $company = Company::find($currentId);
            if (!$company) {
                break;
            }

            $currentId = $company->parent_company_id;
            $depth++;
        }

        return false;
    }
}
