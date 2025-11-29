<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Company;
use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCompanyRepository implements CompanyRepositoryInterface
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
        return Company::where('is_active', true)->get()->all();
    }

    public function getSubsidiaries(string $parentCompanyId): array
    {
        return Company::where('parent_company_id', $parentCompanyId)->get()->all();
    }

    public function getParentChain(string $companyId): array
    {
        $chain = [];
        $company = $this->findById($companyId);

        while ($company && $company->getParentCompanyId()) {
            $parent = $this->findById($company->getParentCompanyId());
            if ($parent) {
                array_unshift($chain, $parent);
                $company = $parent;
            } else {
                break;
            }
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
        return $company;
    }

    public function delete(string $id): bool
    {
        $company = Company::find($id);
        if ($company) {
            return $company->delete();
        }
        return false;
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
        if ($companyId === $proposedParentId) {
            return true;
        }

        $parent = $this->findById($proposedParentId);
        while ($parent) {
            if ($parent->getId() === $companyId) {
                return true;
            }
            if (!$parent->getParentCompanyId()) {
                break;
            }
            $parent = $this->findById($parent->getParentCompanyId());
        }

        return false;
    }
}
