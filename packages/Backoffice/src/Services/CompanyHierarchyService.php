<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Contracts\Query\CompanyQueryInterface;
use Nexus\Backoffice\ValueObjects\CompanyStatus;

/**
 * Domain service for company hierarchy and business logic operations.
 *
 * Extracted from CompanyRepositoryInterface to follow ISP and proper layering.
 * Business logic belongs in services, not repositories.
 */
final readonly class CompanyHierarchyService
{
    public function __construct(
        private readonly CompanyQueryInterface $companyQuery,
    ) {}

    /**
     * Get all active companies.
     *
     * @return array<CompanyInterface> Active companies
     */
    public function getActive(): array
    {
        $allCompanies = $this->companyQuery->getAll();
        
        return array_filter(
            $allCompanies,
            fn(CompanyInterface $company) => $company->getStatus() === CompanyStatus::Active
        );
    }

    /**
     * Get all subsidiaries of a parent company.
     *
     * @param string $parentCompanyId Parent company identifier
     * @return array<CompanyInterface> Subsidiary companies
     */
    public function getSubsidiaries(string $parentCompanyId): array
    {
        $allCompanies = $this->companyQuery->getAll();
        
        return array_filter(
            $allCompanies,
            fn(CompanyInterface $company) => $company->getParentCompanyId() === $parentCompanyId
        );
    }

    /**
     * Get the parent company chain for a company.
     *
     * Returns array of companies from immediate parent up to root.
     *
     * @param string $companyId Company identifier
     * @return array<CompanyInterface> Parent chain (ordered from immediate parent to root)
     */
    public function getParentChain(string $companyId): array
    {
        $chain = [];
        $currentCompany = $this->companyQuery->findById($companyId);
        
        if ($currentCompany === null) {
            return [];
        }

        $parentId = $currentCompany->getParentCompanyId();
        
        while ($parentId !== null) {
            $parent = $this->companyQuery->findById($parentId);
            
            if ($parent === null) {
                break;
            }
            
            $chain[] = $parent;
            $parentId = $parent->getParentCompanyId();
        }
        
        return $chain;
    }

    /**
     * Check for circular parent reference.
     *
     * Prevents a company from being its own ancestor.
     *
     * @param string $companyId Company identifier
     * @param string $proposedParentId Proposed parent company identifier
     * @return bool True if circular reference detected
     */
    public function hasCircularReference(string $companyId, string $proposedParentId): bool
    {
        // A company cannot be its own parent
        if ($companyId === $proposedParentId) {
            return true;
        }
        
        // Check if companyId appears in the parent chain of proposedParentId
        $parentChain = $this->getParentChain($proposedParentId);
        
        foreach ($parentChain as $ancestor) {
            if ($ancestor->getId() === $companyId) {
                return true;
            }
        }
        
        return false;
    }
}
