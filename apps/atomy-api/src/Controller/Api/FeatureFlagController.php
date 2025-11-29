<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\FeatureFlagRepository;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for application-level feature flags.
 *
 * Provides CRUD operations for managing feature flags in the settings page.
 * All operations are scoped to the current tenant context.
 */
#[Route('/api/settings/feature-flags')]
final class FeatureFlagController
{
    public function __construct(
        private readonly FeatureFlagRepository $repository,
        private readonly FeatureFlagManagerInterface $flagManager,
        private readonly TenantContextInterface $tenantContext,
        private readonly Security $security
    ) {}

    /**
     * List all feature flags for the current tenant.
     */
    #[Route('', name: 'api_feature_flags_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $flags = $this->repository->findByTenant($tenantId);

        return new JsonResponse([
            'data' => array_map(fn($f) => $f->toArray(), $flags),
            'meta' => [
                'total' => count($flags),
                'enabled_count' => $this->repository->countEnabledByTenant($tenantId),
            ],
        ]);
    }

    /**
     * Get a single feature flag by ID.
     */
    #[Route('/{id}', name: 'api_feature_flags_get', methods: ['GET'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function get(string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Feature flag not found'], 404);
        }

        // Check tenant access
        if ($flag->getTenantId() !== $this->tenantContext->getCurrentTenantId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse(['data' => $flag->toArray()]);
    }

    /**
     * Create a new feature flag.
     */
    #[Route('', name: 'api_feature_flags_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Validate required fields
        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        // Validate name format
        if (!preg_match('/^[a-z0-9_\.]{1,100}$/', $data['name'])) {
            return new JsonResponse([
                'error' => 'Invalid flag name. Must match pattern: lowercase, alphanumeric, dots, underscores, max 100 chars'
            ], 400);
        }

        // Check for duplicate name
        if ($this->repository->nameExists($data['name'], $tenantId)) {
            return new JsonResponse(['error' => 'Flag name already exists'], 409);
        }

        // Get current user ID
        $currentUser = $this->security->getUser();
        $userId = $currentUser?->getUserIdentifier() ?? null;

        $flag = $this->repository->create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'enabled' => $data['enabled'] ?? false,
            'strategy' => $data['strategy'] ?? 'system_wide',
            'value' => $data['value'] ?? null,
            'override' => $data['override'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'created_by' => $userId,
        ]);

        return new JsonResponse(['data' => $flag->toArray()], 201);
    }

    /**
     * Update an existing feature flag.
     */
    #[Route('/{id}', name: 'api_feature_flags_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $flag = $this->repository->findById($id);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Feature flag not found'], 404);
        }

        // Check tenant access
        if ($flag->getTenantId() !== $this->tenantContext->getCurrentTenantId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Get current user ID
        $currentUser = $this->security->getUser();
        $userId = $currentUser?->getUserIdentifier() ?? null;
        $data['updated_by'] = $userId;

        $updated = $this->repository->update($id, $data);

        return new JsonResponse(['data' => $updated->toArray()]);
    }

    /**
     * Delete a feature flag.
     */
    #[Route('/{id}', name: 'api_feature_flags_delete', methods: ['DELETE'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function delete(string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Feature flag not found'], 404);
        }

        // Check tenant access
        if ($flag->getTenantId() !== $this->tenantContext->getCurrentTenantId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $this->repository->deleteById($id);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Toggle a feature flag's enabled state.
     */
    #[Route('/{id}/toggle', name: 'api_feature_flags_toggle', methods: ['POST'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function toggle(string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Feature flag not found'], 404);
        }

        // Check tenant access
        if ($flag->getTenantId() !== $this->tenantContext->getCurrentTenantId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $updated = $this->repository->toggle($id);

        return new JsonResponse(['data' => $updated->toArray()]);
    }

    /**
     * Evaluate whether a flag is enabled for the current context.
     */
    #[Route('/{name}/evaluate', name: 'api_feature_flags_evaluate', methods: ['POST'])]
    public function evaluate(string $name, Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Build evaluation context
        $context = new EvaluationContext(
            tenantId: $tenantId,
            userId: $data['user_id'] ?? null,
            sessionId: $data['session_id'] ?? null,
            attributes: $data['attributes'] ?? []
        );

        $isEnabled = $this->flagManager->isEnabled($name, $context);

        return new JsonResponse([
            'flag_name' => $name,
            'enabled' => $isEnabled,
            'context' => [
                'tenant_id' => $context->getTenantId(),
                'user_id' => $context->getUserId(),
            ],
        ]);
    }

    /**
     * Bulk evaluate multiple flags.
     */
    #[Route('/evaluate-bulk', name: 'api_feature_flags_evaluate_bulk', methods: ['POST'])]
    public function evaluateBulk(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $data = json_decode($request->getContent() ?: '{}', true);
        $flagNames = $data['flags'] ?? [];

        if (empty($flagNames) || !is_array($flagNames)) {
            return new JsonResponse(['error' => 'flags array is required'], 400);
        }

        // Build evaluation context
        $context = new EvaluationContext(
            tenantId: $tenantId,
            userId: $data['user_id'] ?? null,
            sessionId: $data['session_id'] ?? null,
            attributes: $data['attributes'] ?? []
        );

        $results = $this->flagManager->evaluateMany($flagNames, $context);

        return new JsonResponse([
            'flags' => $results,
            'context' => [
                'tenant_id' => $context->getTenantId(),
                'user_id' => $context->getUserId(),
            ],
        ]);
    }

    /**
     * Get available strategies and their descriptions.
     */
    #[Route('/strategies', name: 'api_feature_flags_strategies', methods: ['GET'])]
    public function strategies(): JsonResponse
    {
        $strategies = [];
        foreach (FlagStrategy::cases() as $strategy) {
            $strategies[] = [
                'value' => $strategy->value,
                'name' => $strategy->name,
                'description' => match ($strategy) {
                    FlagStrategy::SYSTEM_WIDE => 'Enabled/disabled globally for all users',
                    FlagStrategy::PERCENTAGE_ROLLOUT => 'Enabled for a percentage of users based on stable identifier',
                    FlagStrategy::TENANT_LIST => 'Enabled only for specific tenants',
                    FlagStrategy::USER_LIST => 'Enabled only for specific users',
                    FlagStrategy::CUSTOM => 'Custom evaluation logic via CustomEvaluatorInterface',
                },
            ];
        }

        $overrides = [];
        foreach (FlagOverride::cases() as $override) {
            $overrides[] = [
                'value' => $override->value,
                'name' => $override->name,
                'description' => match ($override) {
                    FlagOverride::FORCE_ON => 'Force the flag to always be enabled (kill switch ON)',
                    FlagOverride::FORCE_OFF => 'Force the flag to always be disabled (kill switch OFF)',
                },
            ];
        }

        return new JsonResponse([
            'strategies' => $strategies,
            'overrides' => $overrides,
        ]);
    }
}
