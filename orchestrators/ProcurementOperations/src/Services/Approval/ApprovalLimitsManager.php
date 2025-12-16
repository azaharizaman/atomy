<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Approval;

use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\ProcurementOperations\Contracts\ApprovalLimitsManagerInterface;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckRequest;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckResult;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitConfig;
use Nexus\ProcurementOperations\Exceptions\ApprovalLimitsException;
use Nexus\ProcurementOperations\ValueObjects\ApprovalAuthority;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manages approval limit configurations for procurement documents.
 *
 * This service handles:
 * - CRUD operations for approval limit configurations
 * - Resolution of effective limits based on role/department/user hierarchy
 * - Validation of user approval authority
 * - Integration with Settings package for persistence
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service owns limit configuration management
 * - Integrates with Setting and Identity packages
 * - Returns structured results for coordinator use
 *
 * Limit Resolution Order (highest priority first):
 * 1. User-specific overrides
 * 2. Department-specific limits
 * 3. Role-based limits (highest role wins)
 * 4. Default tenant limits
 */
final readonly class ApprovalLimitsManager implements ApprovalLimitsManagerInterface
{
    /**
     * Setting key for approval limits configuration.
     */
    private const SETTING_KEY = 'procurement.approval.limits';

    /**
     * Supported document types for approval limits.
     */
    private const DOCUMENT_TYPES = [
        'requisition',
        'purchase_order',
        'payment',
        'vendor_invoice',
        'goods_receipt',
        'credit_memo',
    ];

    /**
     * Cache for loaded configurations (request-scoped).
     *
     * @var array<string, ApprovalLimitConfig>
     */
    private array $configCache;

    public function __construct(
        private SettingsManager $settingsManager,
        private UserQueryInterface $userQuery,
        private RoleQueryInterface $roleQuery,
        private TenantContextInterface $tenantContext,
        private ?LoggerInterface $logger = null,
    ) {
        $this->configCache = [];
    }

    /**
     * Get logger instance.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(string $tenantId): ApprovalLimitConfig
    {
        // Check request-scoped cache
        if (isset($this->configCache[$tenantId])) {
            return $this->configCache[$tenantId];
        }

        $this->getLogger()->debug('Loading approval limits configuration', [
            'tenant_id' => $tenantId,
        ]);

        $data = $this->settingsManager->get(
            key: self::SETTING_KEY,
            default: null,
            tenantId: $tenantId
        );

        if ($data === null) {
            $this->getLogger()->info('No configuration found, using defaults', [
                'tenant_id' => $tenantId,
            ]);

            return ApprovalLimitConfig::createDefault();
        }

        $config = ApprovalLimitConfig::fromArray(
            is_array($data) ? $data : json_decode((string) $data, true, 512, JSON_THROW_ON_ERROR)
        );

        // Cache for this request
        $this->configCache[$tenantId] = $config;

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function saveConfiguration(string $tenantId, ApprovalLimitConfig $config): void
    {
        $this->getLogger()->info('Saving approval limits configuration', [
            'tenant_id' => $tenantId,
        ]);

        // Validate configuration
        $this->validateConfiguration($config);

        try {
            $this->settingsManager->setTenantSetting(
                tenantId: $tenantId,
                key: self::SETTING_KEY,
                value: $config->toArray()
            );

            // Update cache
            $this->configCache[$tenantId] = $config;

            $this->getLogger()->info('Configuration saved successfully', [
                'tenant_id' => $tenantId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to save configuration', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw ApprovalLimitsException::saveFailed($tenantId, $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAuthority(
        string $tenantId,
        string $userId,
        ?string $departmentId = null
    ): ApprovalAuthority {
        $this->getLogger()->debug('Resolving user approval authority', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'department_id' => $departmentId,
        ]);

        $config = $this->getConfiguration($tenantId);

        // Get user's roles
        $roles = $this->userQuery->getUserRoles($userId);
        $roleIds = array_map(fn(RoleInterface $role) => $role->getId(), $roles);
        $roleNames = array_map(fn(RoleInterface $role) => $role->getName(), $roles);

        // Resolve limits using priority order
        $limits = $this->resolveLimits(
            config: $config,
            userId: $userId,
            roleIds: $roleIds,
            departmentId: $departmentId
        );

        $hasOverrides = isset($config->userOverrides[$userId]);

        $this->getLogger()->debug('Authority resolved', [
            'user_id' => $userId,
            'limits' => $limits,
            'roles' => $roleNames,
            'has_overrides' => $hasOverrides,
        ]);

        return new ApprovalAuthority(
            userId: $userId,
            limits: $limits,
            roles: $roleNames,
            departmentId: $departmentId,
            hasOverrides: $hasOverrides,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkApprovalLimit(ApprovalLimitCheckRequest $request): ApprovalLimitCheckResult
    {
        $this->getLogger()->debug('Checking approval limit', [
            'tenant_id' => $request->tenantId,
            'user_id' => $request->userId,
            'document_type' => $request->documentType,
            'amount_cents' => $request->amountCents,
        ]);

        // Validate document type
        if (! in_array($request->documentType, self::DOCUMENT_TYPES, true)) {
            $this->getLogger()->warning('Invalid document type', [
                'document_type' => $request->documentType,
            ]);

            return ApprovalLimitCheckResult::noAuthority(
                $request->amountCents,
                sprintf('Invalid document type: %s', $request->documentType)
            );
        }

        // Get user's authority
        $authority = $this->getUserAuthority(
            $request->tenantId,
            $request->userId,
            $request->departmentId
        );

        if (! $authority->hasAnyAuthority()) {
            $this->getLogger()->warning('User has no approval authority', [
                'user_id' => $request->userId,
            ]);

            return ApprovalLimitCheckResult::noAuthority(
                $request->amountCents,
                'User has no approval authority configured'
            );
        }

        $effectiveLimit = $authority->getLimitForType($request->documentType);

        if ($effectiveLimit === 0) {
            return ApprovalLimitCheckResult::noAuthority(
                $request->amountCents,
                sprintf('User has no approval authority for %s', $request->documentType)
            );
        }

        // Determine limit source for reporting
        $limitSource = $this->determineLimitSource(
            $this->getConfiguration($request->tenantId),
            $request->userId,
            $authority->roles,
            $request->departmentId,
            $request->documentType
        );

        $warnings = [];

        // Check for high utilization warning (>80% of limit)
        if ($effectiveLimit !== PHP_INT_MAX) {
            $utilization = ($request->amountCents / $effectiveLimit) * 100;
            if ($utilization > 80 && $utilization <= 100) {
                $warnings[] = sprintf(
                    'Approval uses %.1f%% of limit for %s',
                    $utilization,
                    $request->documentType
                );
            }
        }

        if ($authority->canApprove($request->documentType, $request->amountCents)) {
            $this->getLogger()->info('Approval within limit', [
                'user_id' => $request->userId,
                'document_type' => $request->documentType,
                'amount_cents' => $request->amountCents,
                'effective_limit_cents' => $effectiveLimit,
            ]);

            return ApprovalLimitCheckResult::approved(
                effectiveLimitCents: $effectiveLimit,
                requestedAmountCents: $request->amountCents,
                limitSource: $limitSource['source'],
                limitSourceId: $limitSource['source_id'],
                warnings: $warnings,
            );
        }

        // Determine escalation level required
        $escalationRequired = $this->determineEscalationLevel(
            $this->getConfiguration($request->tenantId),
            $request->amountCents
        );

        $this->getLogger()->info('Approval limit exceeded, escalation required', [
            'user_id' => $request->userId,
            'document_type' => $request->documentType,
            'amount_cents' => $request->amountCents,
            'effective_limit_cents' => $effectiveLimit,
            'escalation_required' => $escalationRequired,
        ]);

        return ApprovalLimitCheckResult::exceeds(
            effectiveLimitCents: $effectiveLimit,
            requestedAmountCents: $request->amountCents,
            limitSource: $limitSource['source'],
            limitSourceId: $limitSource['source_id'],
            escalationRequired: $escalationRequired,
            warnings: $warnings,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleLimits(string $tenantId, string $roleId): array
    {
        $config = $this->getConfiguration($tenantId);

        return $config->roleLimits[$roleId] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setRoleLimits(string $tenantId, string $roleId, array $limits): void
    {
        $this->getLogger()->info('Setting role limits', [
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
        ]);

        // Validate limits
        $this->validateLimits($limits);

        $config = $this->getConfiguration($tenantId);
        $updatedConfig = $config->withRoleLimits($roleId, $limits);

        $this->saveConfiguration($tenantId, $updatedConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getDepartmentLimits(string $tenantId, string $departmentId): array
    {
        $config = $this->getConfiguration($tenantId);

        return $config->departmentLimits[$departmentId] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setDepartmentLimits(string $tenantId, string $departmentId, array $limits): void
    {
        $this->getLogger()->info('Setting department limits', [
            'tenant_id' => $tenantId,
            'department_id' => $departmentId,
        ]);

        // Validate limits
        $this->validateLimits($limits);

        $config = $this->getConfiguration($tenantId);
        $updatedConfig = $config->withDepartmentLimits($departmentId, $limits);

        $this->saveConfiguration($tenantId, $updatedConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserOverrides(string $tenantId, string $userId): ?array
    {
        $config = $this->getConfiguration($tenantId);

        return $config->userOverrides[$userId] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserOverrides(string $tenantId, string $userId, ?array $limits): void
    {
        $this->getLogger()->info('Setting user limit overrides', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $limits === null ? 'clear' : 'set',
        ]);

        if ($limits !== null) {
            $this->validateLimits($limits);
        }

        $config = $this->getConfiguration($tenantId);
        $updatedConfig = $config->withUserOverrides($userId, $limits);

        $this->saveConfiguration($tenantId, $updatedConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function resetToDefaults(string $tenantId): void
    {
        $this->getLogger()->info('Resetting configuration to defaults', [
            'tenant_id' => $tenantId,
        ]);

        $defaultConfig = ApprovalLimitConfig::createDefault();
        $this->saveConfiguration($tenantId, $defaultConfig);
    }

    /**
     * Resolve effective limits using priority hierarchy.
     *
     * Priority order (highest first):
     * 1. User-specific overrides
     * 2. Department-specific limits
     * 3. Role-based limits (highest role wins)
     * 4. Default tenant limits
     *
     * @param array<string> $roleIds
     * @return array<string, int>
     */
    private function resolveLimits(
        ApprovalLimitConfig $config,
        string $userId,
        array $roleIds,
        ?string $departmentId
    ): array {
        $limits = [];

        foreach (self::DOCUMENT_TYPES as $docType) {
            $limits[$docType] = $this->resolveLimit(
                $config,
                $userId,
                $roleIds,
                $departmentId,
                $docType
            );
        }

        return $limits;
    }

    /**
     * Resolve a single limit for a document type.
     */
    private function resolveLimit(
        ApprovalLimitConfig $config,
        string $userId,
        array $roleIds,
        ?string $departmentId,
        string $documentType
    ): int {
        // 1. Check user overrides (highest priority)
        $userOverride = $config->getUserOverrideLimit($userId, $documentType);
        if ($userOverride !== null) {
            return $userOverride;
        }

        // 2. Check department limits
        if ($departmentId !== null) {
            $deptLimit = $config->getDepartmentLimit($departmentId, $documentType);
            if ($deptLimit !== null) {
                return $deptLimit;
            }
        }

        // 3. Check role limits (take the highest)
        $highestRoleLimit = 0;
        foreach ($roleIds as $roleId) {
            $roleLimit = $config->getRoleLimit($roleId, $documentType);
            if ($roleLimit !== null && $roleLimit > $highestRoleLimit) {
                $highestRoleLimit = $roleLimit;
            }
        }

        if ($highestRoleLimit > 0) {
            return $highestRoleLimit;
        }

        // 4. Fall back to default
        return $config->getDefaultLimit($documentType);
    }

    /**
     * Determine the source of the applied limit.
     *
     * @param array<string> $roleNames
     * @return array{source: string, source_id: string|null}
     */
    private function determineLimitSource(
        ApprovalLimitConfig $config,
        string $userId,
        array $roleNames,
        ?string $departmentId,
        string $documentType
    ): array {
        // Check user override first
        if ($config->getUserOverrideLimit($userId, $documentType) !== null) {
            return ['source' => 'user_override', 'source_id' => $userId];
        }

        // Check department
        if ($departmentId !== null && $config->getDepartmentLimit($departmentId, $documentType) !== null) {
            return ['source' => 'department', 'source_id' => $departmentId];
        }

        // Check roles (find the one that provides the limit)
        foreach ($roleNames as $roleName) {
            if ($config->getRoleLimit($roleName, $documentType) !== null) {
                return ['source' => 'role', 'source_id' => $roleName];
            }
        }

        // Default
        return ['source' => 'default', 'source_id' => null];
    }

    /**
     * Determine escalation level required for an amount.
     */
    private function determineEscalationLevel(ApprovalLimitConfig $config, int $amountCents): string
    {
        foreach ($config->thresholds as $threshold) {
            if ($threshold->containsAmount($amountCents)) {
                return $threshold->approverLevel;
            }
        }

        return 'board_approval';
    }

    /**
     * Validate limit configuration.
     *
     * @throws ApprovalLimitsException
     */
    private function validateConfiguration(ApprovalLimitConfig $config): void
    {
        // Validate default limits
        $this->validateLimits($config->defaultLimits);

        // Validate role limits
        foreach ($config->roleLimits as $roleId => $limits) {
            $this->validateLimits($limits);
        }

        // Validate department limits
        foreach ($config->departmentLimits as $deptId => $limits) {
            $this->validateLimits($limits);
        }

        // Validate user overrides
        foreach ($config->userOverrides as $userId => $limits) {
            $this->validateLimits($limits);
        }
    }

    /**
     * Validate a set of limits.
     *
     * @param array<string, int> $limits
     * @throws ApprovalLimitsException
     */
    private function validateLimits(array $limits): void
    {
        foreach ($limits as $docType => $value) {
            // Validate document type
            if (! in_array($docType, self::DOCUMENT_TYPES, true)) {
                throw ApprovalLimitsException::invalidDocumentType($docType, self::DOCUMENT_TYPES);
            }

            // Validate value
            if ($value < 0) {
                throw ApprovalLimitsException::invalidLimitValue($docType, $value);
            }
        }
    }
}
