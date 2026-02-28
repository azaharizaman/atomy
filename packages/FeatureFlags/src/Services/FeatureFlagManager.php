<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Services;

use Nexus\FeatureFlags\Contracts\FlagAuditChangeInterface;
use Nexus\FeatureFlags\Contracts\FlagAuditQueryInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Psr\Log\LoggerInterface;

/**
 * Main feature flag manager orchestrating repository and evaluation.
 *
 * Responsibilities:
 * - Normalize context (array → EvaluationContext)
 * - Load flag definitions from repository
 * - Delegate evaluation to evaluator
 * - Log evaluation results
 * - Provide fail-closed security (defaultIfNotFound)
 * - Optionally record changes via FlagAuditChangeInterface (Nexus\AuditLogger)
 * - Optionally provide query history via FlagAuditQueryInterface (Nexus\EventStream)
 */
final readonly class FeatureFlagManager implements FeatureFlagManagerInterface
{
    public function __construct(
        private FlagRepositoryInterface $repository,
        private FlagEvaluatorInterface $evaluator,
        private LoggerInterface $logger,
        private ?FlagAuditChangeInterface $auditChange = null,
        private ?FlagAuditQueryInterface $auditQuery = null
    ) {
    }

    public function isEnabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool {
        $context = $this->normalizeContext($context);
        $tenantId = $context->tenantId;

        // Load flag from repository
        $flag = $this->repository->findByName($name, $tenantId);

        // Fail-closed security: return default if flag not found
        if ($flag === null) {
            $this->logger->info('Feature flag not found', [
                'flag' => $name,
                'tenant_id' => $tenantId,
                'default_returned' => $defaultIfNotFound,
            ]);

            return $defaultIfNotFound;
        }

        // Evaluate flag
        try {
            $result = $this->evaluator->evaluate($flag, $context);

            $this->logger->info('Feature flag evaluated', [
                'flag' => $name,
                'result' => $result,
                'tenant_id' => $tenantId,
                'strategy' => $flag->getStrategy()->value,
                'override' => $flag->getOverride()?->value,
                'enabled' => $flag->isEnabled(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            // Log error and fail closed
            $this->logger->error('Feature flag evaluation failed', [
                'flag' => $name,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            // Fail-closed: return false on evaluation error
            return false;
        }
    }

    public function isDisabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool {
        return !$this->isEnabled($name, $context, $defaultIfNotFound);
    }

    public function evaluateMany(
        array $flagNames,
        array|EvaluationContext $context = []
    ): array {
        if (empty($flagNames)) {
            return [];
        }

        $context = $this->normalizeContext($context);
        $tenantId = $context->tenantId;

        // Bulk load flags from repository
        $flags = $this->repository->findMany($flagNames, $tenantId);

        // Evaluate found flags
        $results = [];
        if (!empty($flags)) {
            try {
                $results = $this->evaluator->evaluateMany($flags, $context);
            } catch (\Throwable $e) {
                $this->logger->error('Bulk flag evaluation failed', [
                    'flags' => array_keys($flags),
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fill in false for flags not found
        foreach ($flagNames as $name) {
            if (!isset($results[$name])) {
                $results[$name] = false;
            }
        }

        $this->logger->info('Bulk feature flags evaluated', [
            'flags' => $flagNames,
            'tenant_id' => $tenantId,
            'results' => $results,
            'found_count' => count($flags),
        ]);

        return $results;
    }

    /**
     * Normalize context from array or EvaluationContext.
     *
     * @param array<string, mixed>|EvaluationContext $context
     * @return EvaluationContext
     */
    private function normalizeContext(array|EvaluationContext $context): EvaluationContext
    {
        if ($context instanceof EvaluationContext) {
            return $context;
        }

        return EvaluationContext::fromArray($context);
    }

    /**
     * Check if audit change tracking is available.
     *
     * @return bool True if FlagAuditChangeInterface is configured
     */
    public function hasAuditChange(): bool
    {
        return $this->auditChange !== null;
    }

    /**
     * Check if audit query capability is available.
     *
     * @return bool True if FlagAuditQueryInterface is configured
     */
    public function hasAuditQuery(): bool
    {
        return $this->auditQuery !== null;
    }

    /**
     * Get the audit query interface for historical queries.
     *
     * @return FlagAuditQueryInterface|null The audit query interface
     */
    public function getAuditQuery(): ?FlagAuditQueryInterface
    {
        return $this->auditQuery;
    }

    /**
     * Get the audit change interface for recording changes.
     *
     * Internal use only.
     *
     * @return FlagAuditChangeInterface|null The audit change interface
     */
    private function getAuditChange(): ?FlagAuditChangeInterface
    {
        return $this->auditChange;
    }
}
