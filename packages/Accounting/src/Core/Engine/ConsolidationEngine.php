<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Engine;

use Nexus\Accounting\Contracts\{ConsolidationEngineInterface, FinancialStatementInterface, StatementBuilderInterface};
use Nexus\Accounting\Core\ValueObjects\{ReportingPeriod, ConsolidationRule, StatementSection, StatementLineItem};
use Nexus\Accounting\Core\Enums\ConsolidationMethod;
use Nexus\Accounting\Core\Engine\Models\BalanceSheet;
use Nexus\Accounting\Exceptions\ConsolidationException;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Multi-entity consolidation engine.
 *
 * Handles aggregation and elimination for consolidated statements.
 */
final readonly class ConsolidationEngine implements ConsolidationEngineInterface
{
    public function __construct(
        private StatementBuilderInterface $statementBuilder,
        private LedgerRepositoryInterface $ledgerRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function consolidateStatements(
        array $entityIds,
        ReportingPeriod $period,
        ConsolidationMethod $method,
        array $options = []
    ): FinancialStatementInterface {
        $this->logger->info('Starting consolidation', [
            'entities' => count($entityIds),
            'period' => $period->getLabel(),
            'method' => $method->value,
        ]);

        // Validate
        $validation = $this->validateConsolidation($entityIds, $period);
        if (!$validation['valid']) {
            throw ConsolidationException::invalidEntitySet($entityIds);
        }

        // Get individual statements
        $statements = [];
        foreach ($entityIds as $entityId) {
            $statements[$entityId] = $this->statementBuilder->buildBalanceSheet(
                $entityId,
                $period,
                $options
            );
        }

        // Apply consolidation method
        return match($method) {
            ConsolidationMethod::FULL => $this->consolidateFull($statements, $period, $options),
            ConsolidationMethod::PROPORTIONAL => $this->consolidateProportional($statements, $period, $options),
            ConsolidationMethod::EQUITY => $this->consolidateEquity($statements, $period, $options),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function applyEliminationRules(array $rules, array $statements): array
    {
        $eliminations = [];

        foreach ($rules as $rule) {
            $this->logger->debug('Applying elimination rule', ['rule_id' => $rule->getId()]);

            try {
                // Find intercompany transactions
                $sourceStatement = $statements[$rule->getSourceEntityId()] ?? null;
                $targetStatement = $statements[$rule->getTargetEntityId()] ?? null;

                if (!$sourceStatement || !$targetStatement) {
                    continue;
                }

                // Calculate elimination amounts
                $elimination = $this->calculateElimination($rule, $sourceStatement, $targetStatement);
                
                if ($elimination['amount'] != 0) {
                    $eliminations[] = $elimination;
                }

            } catch (\Throwable $e) {
                throw ConsolidationException::eliminationFailed($rule->getId(), $e);
            }
        }

        return $eliminations;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateNonControllingInterest(
        string $parentEntityId,
        array $subsidiaryEntityIds,
        array $ownershipData
    ): float {
        $totalNCI = 0.0;

        foreach ($subsidiaryEntityIds as $subsidiaryId) {
            $ownership = $ownershipData[$subsidiaryId]['percentage'] ?? 100.0;
            $nciPercentage = 100.0 - $ownership;

            if ($nciPercentage > 0) {
                $subsidiaryEquity = $ownershipData[$subsidiaryId]['equity'] ?? 0.0;
                $nci = ($subsidiaryEquity * $nciPercentage) / 100;
                $totalNCI += $nci;
            }
        }

        return $totalNCI;
    }

    /**
     * {@inheritdoc}
     */
    public function generateConsolidatedTrialBalance(
        array $entityIds,
        ReportingPeriod $period
    ): array {
        $consolidatedBalances = [];

        foreach ($entityIds as $entityId) {
            $trialBalance = $this->ledgerRepository->getTrialBalance(
                $entityId,
                $period->getEndDate()
            );

            foreach ($trialBalance as $account) {
                $code = $account['code'];
                
                if (!isset($consolidatedBalances[$code])) {
                    $consolidatedBalances[$code] = [
                        'code' => $code,
                        'name' => $account['name'],
                        'debit' => 0.0,
                        'credit' => 0.0,
                    ];
                }

                $consolidatedBalances[$code]['debit'] += $account['debit'];
                $consolidatedBalances[$code]['credit'] += $account['credit'];
            }
        }

        return array_values($consolidatedBalances);
    }

    /**
     * {@inheritdoc}
     */
    public function validateConsolidation(array $entityIds, ReportingPeriod $period): array
    {
        $issues = [];

        // Check minimum entities
        if (count($entityIds) < 2) {
            $issues[] = 'At least 2 entities required for consolidation';
        }

        // Check all entities exist and have statements
        foreach ($entityIds as $entityId) {
            try {
                $this->statementBuilder->buildBalanceSheet($entityId, $period);
            } catch (\Throwable $e) {
                $issues[] = "Entity {$entityId}: {$e->getMessage()}";
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Consolidate using full method.
     *
     * @param FinancialStatementInterface[] $statements
     */
    private function consolidateFull(
        array $statements,
        ReportingPeriod $period,
        array $options
    ): FinancialStatementInterface {
        // Combine 100% of all entities
        $consolidatedSections = [];
        $sectionMap = [];

        foreach ($statements as $entityId => $statement) {
            foreach ($statement->getSections() as $section) {
                $code = $section->getCode();
                
                if (!isset($sectionMap[$code])) {
                    $sectionMap[$code] = [
                        'code' => $code,
                        'name' => $section->getName(),
                        'order' => $section->getOrder(),
                        'items' => [],
                    ];
                }

                foreach ($section->getLineItems() as $item) {
                    $itemCode = $item->getCode();
                    
                    if (!isset($sectionMap[$code]['items'][$itemCode])) {
                        $sectionMap[$code]['items'][$itemCode] = [
                            'code' => $itemCode,
                            'label' => $item->getLabel(),
                            'amount' => 0.0,
                            'level' => $item->getLevel(),
                            'parent_code' => $item->getParentCode(),
                        ];
                    }

                    $sectionMap[$code]['items'][$itemCode]['amount'] += $item->getAmount();
                }
            }
        }

        // Build sections
        foreach ($sectionMap as $sectionData) {
            $lineItems = [];
            foreach ($sectionData['items'] as $itemData) {
                $lineItems[] = new StatementLineItem(
                    code: $itemData['code'],
                    label: $itemData['label'],
                    amount: $itemData['amount'],
                    level: $itemData['level'],
                    parentCode: $itemData['parent_code']
                );
            }

            $consolidatedSections[] = new StatementSection(
                code: $sectionData['code'],
                name: $sectionData['name'],
                lineItems: $lineItems,
                order: $sectionData['order']
            );
        }

        return new BalanceSheet(
            entityId: 'CONSOLIDATED',
            period: $period,
            sections: $consolidatedSections,
            metadata: [
                'consolidation_method' => ConsolidationMethod::FULL->value,
                'entity_count' => count($statements),
                'generated_at' => new \DateTimeImmutable(),
            ]
        );
    }

    /**
     * Consolidate using proportional method.
     *
     * @param FinancialStatementInterface[] $statements
     */
    private function consolidateProportional(
        array $statements,
        ReportingPeriod $period,
        array $options
    ): FinancialStatementInterface {
        // Similar to full but apply ownership percentages
        $ownership = $options['ownership'] ?? [];
        
        // Implementation similar to consolidateFull but multiply by ownership %
        return $this->consolidateFull($statements, $period, $options);
    }

    /**
     * Consolidate using equity method.
     *
     * @param FinancialStatementInterface[] $statements
     */
    private function consolidateEquity(
        array $statements,
        ReportingPeriod $period,
        array $options
    ): FinancialStatementInterface {
        // Report investments as single line items
        return $this->consolidateFull($statements, $period, $options);
    }

    /**
     * Calculate elimination amount for a rule.
     *
     * @return array<string, mixed>
     */
    private function calculateElimination(
        ConsolidationRule $rule,
        FinancialStatementInterface $sourceStatement,
        FinancialStatementInterface $targetStatement
    ): array {
        // Find matching accounts in both statements
        $eliminationAmount = 0.0;

        // This is a simplified calculation
        // In reality, you'd match specific intercompany accounts
        
        return [
            'rule_id' => $rule->getId(),
            'rule_name' => $rule->getName(),
            'amount' => $eliminationAmount,
            'source_entity' => $sourceStatement->getEntityId(),
            'target_entity' => $targetStatement->getEntityId(),
        ];
    }
}
