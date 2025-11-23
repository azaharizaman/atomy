<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Engine;

use Nexus\FeatureFlags\Contracts\CustomEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Exceptions\CustomEvaluatorException;
use Nexus\FeatureFlags\Exceptions\InvalidContextException;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use ReflectionClass;

/**
 * Default flag evaluator implementing all evaluation strategies.
 *
 * Evaluation flow:
 * 1. Check override (FORCE_OFF → false, FORCE_ON → true)
 * 2. Check enabled state (if false → false)
 * 3. Apply strategy-specific logic
 */
final readonly class DefaultFlagEvaluator implements FlagEvaluatorInterface
{
    public function __construct(
        private PercentageHasher $percentageHasher
    ) {
    }

    public function evaluate(FlagDefinitionInterface $flag, EvaluationContext $context): bool
    {
        // Step 1: Check override (highest precedence)
        if ($flag->getOverride() === FlagOverride::FORCE_OFF) {
            return false;
        }

        if ($flag->getOverride() === FlagOverride::FORCE_ON) {
            return true;
        }

        // Step 2: Check enabled state
        if (!$flag->isEnabled()) {
            return false;
        }

        // Step 3: Apply strategy-specific logic
        return match ($flag->getStrategy()) {
            FlagStrategy::SYSTEM_WIDE => $this->evaluateSystemWide(),
            FlagStrategy::PERCENTAGE_ROLLOUT => $this->evaluatePercentageRollout($flag, $context),
            FlagStrategy::TENANT_LIST => $this->evaluateTenantList($flag, $context),
            FlagStrategy::USER_LIST => $this->evaluateUserList($flag, $context),
            FlagStrategy::CUSTOM => $this->evaluateCustom($flag, $context),
        };
    }

    public function evaluateMany(array $flags, EvaluationContext $context): array
    {
        $results = [];

        foreach ($flags as $flag) {
            $results[$flag->getName()] = $this->evaluate($flag, $context);
        }

        return $results;
    }

    /**
     * SYSTEM_WIDE: Always enabled (enabled state already checked).
     */
    private function evaluateSystemWide(): bool
    {
        return true;
    }

    /**
     * PERCENTAGE_ROLLOUT: Enabled for percentage of users based on stable identifier.
     *
     * @throws InvalidContextException If no stable identifier available
     */
    private function evaluatePercentageRollout(
        FlagDefinitionInterface $flag,
        EvaluationContext $context
    ): bool {
        $identifier = $context->getStableIdentifier();

        if ($identifier === null) {
            throw InvalidContextException::missingStableIdentifier($flag->getName());
        }

        $percentage = $flag->getValue();
        $bucket = $this->percentageHasher->getBucket($identifier, $flag->getName());

        return $bucket < $percentage;
    }

    /**
     * TENANT_LIST: Enabled if context tenant is in the list.
     */
    private function evaluateTenantList(
        FlagDefinitionInterface $flag,
        EvaluationContext $context
    ): bool {
        if ($context->tenantId === null) {
            // Optional: Could throw exception or return false
            // Choosing false for graceful degradation
            return false;
        }

        $tenantList = $flag->getValue();

        return in_array($context->tenantId, $tenantList, strict: true);
    }

    /**
     * USER_LIST: Enabled if context user is in the list.
     */
    private function evaluateUserList(
        FlagDefinitionInterface $flag,
        EvaluationContext $context
    ): bool {
        if ($context->userId === null) {
            // Optional: Could throw exception or return false
            // Choosing false for graceful degradation
            return false;
        }

        $userList = $flag->getValue();

        return in_array($context->userId, $userList, strict: true);
    }

    /**
     * CUSTOM: Instantiate and execute custom evaluator.
     *
     * @throws CustomEvaluatorException If class invalid or execution fails
     */
    private function evaluateCustom(
        FlagDefinitionInterface $flag,
        EvaluationContext $context
    ): bool {
        $className = $flag->getValue();

        try {
            $reflection = new ReflectionClass($className);
            $evaluator = $reflection->newInstance();
        } catch (\Throwable $e) {
            throw CustomEvaluatorException::instantiationFailed($className, $e);
        }

        if (!$evaluator instanceof CustomEvaluatorInterface) {
            throw CustomEvaluatorException::invalidInterface($className);
        }

        try {
            return $evaluator->evaluate($context);
        } catch (\Throwable $e) {
            throw CustomEvaluatorException::evaluationFailed($className, $e);
        }
    }
}
