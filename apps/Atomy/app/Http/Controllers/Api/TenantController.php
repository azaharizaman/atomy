<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Services\TenantLifecycleService;
use Nexus\Tenant\Services\TenantImpersonationService;
use Nexus\Tenant\ValueObjects\TenantStatus;
use Nexus\Tenant\ValueObjects\TenantSettings;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Nexus\Tenant\Exceptions\InvalidTenantStatusException;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\ImpersonationNotAllowedException;

/**
 * Tenant Management API Controller
 */
class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
        private readonly TenantLifecycleService $lifecycle,
        private readonly TenantImpersonationService $impersonation
    ) {
    }

    /**
     * List all tenants with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $status = $request->get('status');
        $search = $request->get('search');
        $parentId = $request->get('parent_id');

        $filters = [];
        if ($status) {
            // Validate and get enum value
            $filters['status'] = TenantStatus::from($status)->value;
        }
        if ($search) {
            $filters['search'] = $search;
        }
        if ($parentId) {
            $filters['parent_id'] = $parentId;
        }

        $result = $this->repository->all(
            $filters,
            $page,
            $perPage
        );

        return response()->json($result);
    }

    /**
     * Store a new tenant
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:tenants,code',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'subdomain' => 'nullable|string|max:100|unique:tenants,subdomain',
            'database_name' => 'nullable|string|max:100',
            'parent_id' => 'nullable|string|exists:tenants,id',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:3',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
            'metadata' => 'nullable|array',
        ]);

        try {
            $settings = new TenantSettings(
                $validated['timezone'] ?? config('tenant.defaults.timezone'),
                $validated['locale'] ?? config('tenant.defaults.locale'),
                $validated['currency'] ?? config('tenant.defaults.currency'),
                $validated['date_format'] ?? config('tenant.defaults.date_format'),
                $validated['time_format'] ?? config('tenant.defaults.time_format'),
                $validated['metadata'] ?? []
            );

            $tenant = $this->lifecycle->create(
                $validated['code'],
                $validated['name'],
                $validated['email'] ?? null,
                $validated['phone'] ?? null,
                $validated['domain'] ?? null,
                $validated['subdomain'] ?? null,
                $validated['database_name'] ?? null,
                $settings,
                $validated['parent_id'] ?? null
            );

            return response()->json($tenant, 201);
        } catch (DuplicateTenantCodeException|DuplicateTenantDomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Show a specific tenant
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tenant = $this->repository->findById($id);
            return response()->json($tenant);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
    }

    /**
     * Update an existing tenant
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'domain' => 'sometimes|nullable|string|max:255',
            'subdomain' => 'sometimes|nullable|string|max:100',
            'timezone' => 'sometimes|string|max:50',
            'locale' => 'sometimes|string|max:10',
            'currency' => 'sometimes|string|max:3',
            'date_format' => 'sometimes|string|max:20',
            'time_format' => 'sometimes|string|max:20',
            'metadata' => 'sometimes|nullable|array',
        ]);

        try {
            $settings = null;
            if (
                isset($validated['timezone']) || isset($validated['locale']) ||
                isset($validated['currency']) || isset($validated['date_format']) ||
                isset($validated['time_format']) || isset($validated['metadata'])
            ) {
                $tenant = $this->repository->findById($id);
                $settings = new TenantSettings(
                    $validated['timezone'] ?? $tenant->getTimezone(),
                    $validated['locale'] ?? $tenant->getLocale(),
                    $validated['currency'] ?? $tenant->getCurrency(),
                    $validated['date_format'] ?? $tenant->getDateFormat(),
                    $validated['time_format'] ?? $tenant->getTimeFormat(),
                    $validated['metadata'] ?? $tenant->getMetadata()
                );
            }

            $updated = $this->lifecycle->update(
                $id,
                $validated['name'] ?? null,
                $validated['email'] ?? null,
                $validated['phone'] ?? null,
                $validated['domain'] ?? null,
                $validated['subdomain'] ?? null,
                $settings
            );

            return response()->json($updated);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        } catch (DuplicateTenantDomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Activate a tenant
     */
    public function activate(string $id): JsonResponse
    {
        try {
            $tenant = $this->lifecycle->activate($id);
            return response()->json($tenant);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        } catch (InvalidTenantStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Suspend a tenant
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $tenant = $this->lifecycle->suspend($id, $validated['reason'] ?? null);
            return response()->json($tenant);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        } catch (InvalidTenantStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Reactivate a suspended tenant
     */
    public function reactivate(string $id): JsonResponse
    {
        try {
            $tenant = $this->lifecycle->reactivate($id);
            return response()->json($tenant);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        } catch (InvalidTenantStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Archive a tenant (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->lifecycle->archive($id);
            return response()->json(['message' => 'Tenant archived successfully']);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
    }

    /**
     * Permanently delete a tenant
     */
    public function forceDestroy(string $id): JsonResponse
    {
        try {
            $this->lifecycle->delete($id);
            return response()->json(['message' => 'Tenant permanently deleted']);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
    }

    /**
     * Get tenant statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->repository->getStatistics();
        return response()->json($stats);
    }

    /**
     * Start impersonating a tenant
     */
    public function impersonate(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $originalUserId = auth()->id();
            $this->impersonation->startImpersonation(
                $id,
                $originalUserId,
                $validated['reason'] ?? null
            );

            return response()->json(['message' => 'Impersonation started']);
        } catch (TenantNotFoundException $e) {
            return response()->json(['error' => 'Tenant not found'], 404);
        } catch (ImpersonationNotAllowedException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Stop impersonating a tenant
     */
    public function stopImpersonation(): JsonResponse
    {
        try {
            $this->impersonation->endImpersonation();
            return response()->json(['message' => 'Impersonation ended']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get active trials
     */
    public function trials(): JsonResponse
    {
        $trials = $this->repository->getTrials();
        return response()->json($trials);
    }

    /**
     * Get expired trials
     */
    public function expiredTrials(): JsonResponse
    {
        $expired = $this->repository->getExpiredTrials();
        return response()->json($expired);
    }
}
