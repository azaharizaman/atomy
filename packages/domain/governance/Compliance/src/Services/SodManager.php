<?php

declare(strict_types=1);

namespace Nexus\Compliance\Services;

use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\Contracts\SodRuleInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;
use Nexus\Compliance\Contracts\SodViolationInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;
use Nexus\Compliance\Exceptions\DuplicateRuleException;
use Nexus\Compliance\Exceptions\RuleNotFoundException;
use Nexus\Compliance\Exceptions\SodViolationException;
use Nexus\Compliance\ValueObjects\SeverityLevel;
use Psr\Log\LoggerInterface;

/**
 * Service for managing SOD (Segregation of Duties) rules and violations.
 */
final class SodManager implements SodManagerInterface
{
    public function __construct(
        private readonly SodRuleRepositoryInterface $ruleRepository,
        private readonly SodViolationRepositoryInterface $violationRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createRule(
        string $tenantId,
        string $ruleName,
        string $transactionType,
        SeverityLevel $severityLevel,
        string $creatorRole,
        string $approverRole
    ): string {
        $this->logger->info("Creating SOD rule", [
            'tenant_id' => $tenantId,
            'rule_name' => $ruleName,
            'transaction_type' => $transactionType,
            'severity_level' => $severityLevel->value,
        ]);

        // Check for duplicates
        $existingRules = $this->ruleRepository->getRulesByTransactionType($tenantId, $transactionType);
        foreach ($existingRules as $rule) {
            if ($rule->getRuleName() === $ruleName) {
                throw new DuplicateRuleException($ruleName, $transactionType);
            }
        }

        // Rule creation logic will be implemented in application layer
        
        $this->logger->info("SOD rule created successfully", [
            'tenant_id' => $tenantId,
            'rule_name' => $ruleName,
        ]);

        return 'rule-id-placeholder';
    }

    public function validateTransaction(
        string $tenantId,
        string $transactionType,
        string $creatorId,
        string $approverId
    ): bool {
        $this->logger->debug("Validating transaction against SOD rules", [
            'tenant_id' => $tenantId,
            'transaction_type' => $transactionType,
            'creator_id' => $creatorId,
            'approver_id' => $approverId,
        ]);

        // Basic check: creator cannot be approver
        if ($creatorId === $approverId) {
            $this->logger->warning("SOD violation: creator and approver are the same", [
                'tenant_id' => $tenantId,
                'transaction_type' => $transactionType,
                'user_id' => $creatorId,
            ]);

            throw new SodViolationException(
                $transactionType,
                $creatorId,
                $approverId,
                'creator_cannot_approve'
            );
        }

        // Get applicable rules
        $rules = $this->ruleRepository->getRulesByTransactionType($tenantId, $transactionType);

        // Full validation logic will be implemented in Core/Engine layer
        // This is a placeholder for the service skeleton
        
        $this->logger->debug("Transaction validated successfully", [
            'tenant_id' => $tenantId,
            'transaction_type' => $transactionType,
        ]);

        return true;
    }

    public function getRules(string $tenantId): array
    {
        return $this->ruleRepository->getAllRules($tenantId);
    }

    public function getViolations(
        string $tenantId,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        return $this->violationRepository->getViolations($tenantId, $from, $to);
    }

    public function deactivateRule(string $ruleId): void
    {
        $this->logger->info("Deactivating SOD rule", ['rule_id' => $ruleId]);

        $rule = $this->ruleRepository->findById($ruleId);
        if ($rule === null) {
            throw new RuleNotFoundException($ruleId);
        }

        // Deactivation logic will be implemented in application layer
        
        $this->logger->info("SOD rule deactivated successfully", ['rule_id' => $ruleId]);
    }

    public function activateRule(string $ruleId): void
    {
        $this->logger->info("Activating SOD rule", ['rule_id' => $ruleId]);

        $rule = $this->ruleRepository->findById($ruleId);
        if ($rule === null) {
            throw new RuleNotFoundException($ruleId);
        }

        // Activation logic will be implemented in application layer
        
        $this->logger->info("SOD rule activated successfully", ['rule_id' => $ruleId]);
    }
}
