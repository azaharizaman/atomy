<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;
use Nexus\Backoffice\Exceptions\CircularReferenceException;

/**
 * Company Management API Controller
 */
class CompanyController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly CompanyRepositoryInterface $repository
    ) {}

    /**
     * List all companies
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        
        $companies = $status === 'active' 
            ? $this->repository->getActive()
            : $this->repository->getAll();

        return response()->json(['data' => $companies]);
    }

    /**
     * Store a new company
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'registration_date' => 'nullable|date',
            'jurisdiction' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,suspended,dissolved',
            'parent_company_id' => 'nullable|string|exists:companies,id',
            'financial_year_start_month' => 'nullable|integer|min:1|max:12',
            'industry' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        try {
            $company = $this->manager->createCompany($validated);
            return response()->json(['data' => $company], 201);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (CompanyNotFoundException | InvalidOperationException | CircularReferenceException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific company
     */
    public function show(string $id): JsonResponse
    {
        $company = $this->manager->getCompany($id);
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json(['data' => $company]);
    }

    /**
     * Update an existing company
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'registration_date' => 'nullable|date',
            'jurisdiction' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,suspended,dissolved',
            'parent_company_id' => 'nullable|string|exists:companies,id',
            'financial_year_start_month' => 'nullable|integer|min:1|max:12',
            'industry' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        try {
            $company = $this->manager->updateCompany($id, $validated);
            return response()->json(['data' => $company]);
        } catch (CompanyNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidOperationException | CircularReferenceException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a company
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->deleteCompany($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (CompanyNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get subsidiaries of a company
     */
    public function subsidiaries(string $id): JsonResponse
    {
        try {
            $subsidiaries = $this->repository->getSubsidiaries($id);
            return response()->json(['data' => $subsidiaries]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get parent chain of a company
     */
    public function parentChain(string $id): JsonResponse
    {
        try {
            $parentChain = $this->repository->getParentChain($id);
            return response()->json(['data' => $parentChain]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
