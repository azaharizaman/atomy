<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\FeatureFlagRepository;
use App\Repository\UserFlagOverrideRepository;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for user-level feature flag settings.
 *
 * Allows users to manage their personal flag overrides that take
 * precedence over application-level flag settings.
 */
#[Route('/api/user/settings/feature-flags')]
final class UserFlagController
{
    public function __construct(
        private readonly UserFlagOverrideRepository $overrideRepository,
        private readonly FeatureFlagRepository $flagRepository,
        private readonly FeatureFlagManagerInterface $flagManager,
        private readonly TenantContextInterface $tenantContext,
        private readonly Security $security
    ) {}

    /**
     * Get current user's flag overrides.
     */
    #[Route('', name: 'api_user_flags_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->getUserIdentifier();
        $tenantId = $this->tenantContext->getCurrentTenantId();

        // Get user's overrides
        $overrides = $this->overrideRepository->findByUser($userId);

        // Get available flags for context
        $availableFlags = $tenantId !== null
            ? $this->flagRepository->findByTenant($tenantId)
            : [];

        return new JsonResponse([
            'data' => [
                'overrides' => array_map(fn($o) => $o->toArray(), $overrides),
                'available_flags' => array_map(fn($f) => [
                    'name' => $f->getName(),
                    'description' => $f->getDescription(),
                    'enabled' => $f->isEnabled(),
                    'strategy' => $f->getStrategy()->value,
                ], $availableFlags),
            ],
            'meta' => [
                'user_id' => $userId,
                'total_overrides' => count($overrides),
                'active_overrides' => count(array_filter($overrides, fn($o) => $o->isActive())),
            ],
        ]);
    }

    /**
     * Get current user's effective flags (combined app + user overrides).
     */
    #[Route('/effective', name: 'api_user_flags_effective', methods: ['GET'])]
    public function effective(): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->getUserIdentifier();
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        // Get all flags for tenant
        $appFlags = $this->flagRepository->findByTenant($tenantId);

        // Get user's override map
        $userOverrides = $this->overrideRepository->getOverrideMapForUser($userId);

        // Build effective flags list
        $effectiveFlags = [];
        foreach ($appFlags as $flag) {
            $name = $flag->getName();
            $userOverride = $userOverrides[$name] ?? null;

            // Determine effective enabled state
            $effectiveEnabled = match (true) {
                $userOverride === FlagOverride::FORCE_ON => true,
                $userOverride === FlagOverride::FORCE_OFF => false,
                default => $flag->isEnabled(),
            };

            $effectiveFlags[] = [
                'name' => $name,
                'description' => $flag->getDescription(),
                'app_enabled' => $flag->isEnabled(),
                'user_override' => $userOverride?->value,
                'effective_enabled' => $effectiveEnabled,
                'strategy' => $flag->getStrategy()->value,
            ];
        }

        return new JsonResponse([
            'data' => $effectiveFlags,
            'meta' => [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'total_flags' => count($effectiveFlags),
                'user_overrides_applied' => count($userOverrides),
            ],
        ]);
    }

    /**
     * Get a specific override by ID.
     */
    #[Route('/{id}', name: 'api_user_flags_get', methods: ['GET'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function get(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $override = $this->overrideRepository->findById($id);
        if ($override === null) {
            return new JsonResponse(['error' => 'Override not found'], 404);
        }

        // Ensure user owns this override
        if ($override->getUserId() !== $user->getUserIdentifier()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse(['data' => $override->toArray()]);
    }

    /**
     * Create a new user flag override.
     */
    #[Route('', name: 'api_user_flags_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->getUserIdentifier();
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Validate required fields
        if (empty($data['flag_name'])) {
            return new JsonResponse(['error' => 'flag_name is required'], 400);
        }

        if (empty($data['override'])) {
            return new JsonResponse(['error' => 'override is required (force_on or force_off)'], 400);
        }

        // Validate override value
        try {
            FlagOverride::from($data['override']);
        } catch (\ValueError) {
            return new JsonResponse([
                'error' => 'Invalid override value. Must be "force_on" or "force_off"'
            ], 400);
        }

        // Check if flag exists
        $flag = $this->flagRepository->findByName($data['flag_name'], $tenantId);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Flag not found'], 404);
        }

        // Check if override already exists
        if ($this->overrideRepository->overrideExists($userId, $data['flag_name'])) {
            return new JsonResponse([
                'error' => 'Override already exists for this flag. Use PUT to update.'
            ], 409);
        }

        $override = $this->overrideRepository->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'flag_name' => $data['flag_name'],
            'override' => $data['override'],
            'reason' => $data['reason'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $userId,
        ]);

        return new JsonResponse(['data' => $override->toArray()], 201);
    }

    /**
     * Update an existing user flag override.
     */
    #[Route('/{id}', name: 'api_user_flags_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $override = $this->overrideRepository->findById($id);
        if ($override === null) {
            return new JsonResponse(['error' => 'Override not found'], 404);
        }

        // Ensure user owns this override
        if ($override->getUserId() !== $user->getUserIdentifier()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Validate override value if provided
        if (isset($data['override'])) {
            try {
                FlagOverride::from($data['override']);
            } catch (\ValueError) {
                return new JsonResponse([
                    'error' => 'Invalid override value. Must be "force_on" or "force_off"'
                ], 400);
            }
        }

        $updated = $this->overrideRepository->update($id, $data);

        return new JsonResponse(['data' => $updated->toArray()]);
    }

    /**
     * Delete a user flag override.
     */
    #[Route('/{id}', name: 'api_user_flags_delete', methods: ['DELETE'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $override = $this->overrideRepository->findById($id);
        if ($override === null) {
            return new JsonResponse(['error' => 'Override not found'], 404);
        }

        // Ensure user owns this override
        if ($override->getUserId() !== $user->getUserIdentifier()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $this->overrideRepository->deleteById($id);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Quick toggle: Set or remove override for a flag.
     *
     * Convenience endpoint for simple on/off/default controls.
     */
    #[Route('/toggle/{flagName}', name: 'api_user_flags_toggle', methods: ['POST'])]
    public function toggle(string $flagName, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->getUserIdentifier();
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $data = json_decode($request->getContent() ?: '{}', true);
        $action = $data['action'] ?? null; // 'force_on', 'force_off', 'default'

        if (!in_array($action, ['force_on', 'force_off', 'default'], true)) {
            return new JsonResponse([
                'error' => 'action required: force_on, force_off, or default'
            ], 400);
        }

        // Check if flag exists
        $flag = $this->flagRepository->findByName($flagName, $tenantId);
        if ($flag === null) {
            return new JsonResponse(['error' => 'Flag not found'], 404);
        }

        // Find existing override
        $existingOverride = $this->overrideRepository->findUserOverride($userId, $flagName);

        if ($action === 'default') {
            // Remove override - use default behavior
            if ($existingOverride !== null) {
                $this->overrideRepository->deleteById($existingOverride->getId());
            }

            return new JsonResponse([
                'data' => [
                    'flag_name' => $flagName,
                    'override' => null,
                    'effective_enabled' => $flag->isEnabled(),
                    'action' => 'removed_override',
                ],
            ]);
        }

        // Set or update override
        if ($existingOverride !== null) {
            $updated = $this->overrideRepository->update($existingOverride->getId(), [
                'override' => $action,
            ]);
            $overrideData = $updated->toArray();
        } else {
            $override = $this->overrideRepository->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'flag_name' => $flagName,
                'override' => $action,
                'created_by' => $userId,
            ]);
            $overrideData = $override->toArray();
        }

        return new JsonResponse([
            'data' => [
                'flag_name' => $flagName,
                'override' => $action,
                'effective_enabled' => $action === 'force_on',
                'override_details' => $overrideData,
            ],
        ]);
    }

    /**
     * Get available override options.
     */
    #[Route('/options', name: 'api_user_flags_options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $overrideOptions = [];
        foreach (FlagOverride::cases() as $override) {
            $overrideOptions[] = [
                'value' => $override->value,
                'name' => $override->name,
                'description' => match ($override) {
                    FlagOverride::FORCE_ON => 'Always enable this feature for me',
                    FlagOverride::FORCE_OFF => 'Always disable this feature for me',
                },
            ];
        }

        return new JsonResponse([
            'override_options' => $overrideOptions,
            'actions' => [
                ['value' => 'force_on', 'description' => 'Force enable'],
                ['value' => 'force_off', 'description' => 'Force disable'],
                ['value' => 'default', 'description' => 'Use application default'],
            ],
        ]);
    }
}
