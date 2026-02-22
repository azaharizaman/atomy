<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for GL reconciliation data aggregation.
 *
 * Aggregates data from:
 * - Receivable package (AR subledger balance)
 * - Payable package (AP subledger balance)
 * - Assets package (Fixed asset subledger balance)
 * - JournalEntry package (GL control account balances)
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationProvider implements GLReconciliationProviderInterface
{
    public function __construct(
        private object $receivableQuery,  // ReceivableQueryInterface
        private object $payableQuery,  // PayableQueryInterface
        private object $assetQuery,  // AssetQueryInterface
        private object $glQuery,  // LedgerQueryInterface
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getSubledgerBalance(string $tenantId, string $periodId, string $subledgerType): array
    {
        $this->logger->debug('Fetching subledger balance', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'subledger_type' => $subledgerType,
        ]);

        try {
            $result = match ($subledgerType) {
                'receivable', 'AR' => $this->getReceivableBalance($tenantId, $periodId),
                'payable', 'AP' => $this->getPayableBalance($tenantId, $periodId),
                'asset', 'FA' => $this->getAssetBalance($tenantId, $periodId),
                'inventory' => $this->getInventoryBalance($tenantId, $periodId),
                default => [
                    'balance' => '0',
                    'currency' => null,
                    'error' => "Unknown subledger type: {$subledgerType}",
                ],
            };

            return array_merge($result, [
                'subledger_type' => $subledgerType,
                'period_id' => $periodId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch subledger balance', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'subledger_type' => $subledgerType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getGLBalance(string $tenantId, string $periodId, string $accountId): array
    {
        $this->logger->debug('Fetching GL control account balance', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'account_id' => $accountId,
        ]);

        try {
            $balance = $this->glQuery->getAccountBalance($tenantId, $accountId, $periodId);

            return [
                'account_id' => $accountId,
                'period_id' => $periodId,
                'balance' => $balance->getBalance(),
                'currency' => $balance->getCurrency(),
                'debit_total' => $balance->getDebitTotal(),
                'credit_total' => $balance->getCreditTotal(),
                'as_of_date' => $balance->getAsOfDate()->format('Y-m-d'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch GL balance', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getDiscrepancies(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Fetching reconciliation discrepancies', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            $subledgerTypes = ['receivable', 'payable', 'asset'];
            $discrepancies = [];

            foreach ($subledgerTypes as $type) {
                $subledgerData = $this->getSubledgerBalance($tenantId, $periodId, $type);
                $controlAccountCode = $this->getControlAccountCode($tenantId, $type);

                if ($controlAccountCode === null) {
                    continue;
                }

                $glData = $this->getGLBalance($tenantId, $periodId, $controlAccountCode);

                $subledgerBalance = $subledgerData['balance'] ?? '0';
                $glBalance = $glData['balance'] ?? '0';
                $variance = (string)((float) $subledgerBalance - (float) $glBalance);

                if ((float) $variance !== 0.0) {
                    $discrepancies[] = [
                        'subledger_type' => $type,
                        'subledger_balance' => $subledgerBalance,
                        'gl_balance' => $glBalance,
                        'variance' => $variance,
                        'control_account' => $controlAccountCode,
                        'details' => $this->getDetailedDiscrepancies($tenantId, $periodId, $type),
                    ];
                }
            }

            return [
                'period_id' => $periodId,
                'has_discrepancies' => count($discrepancies) > 0,
                'discrepancy_count' => count($discrepancies),
                'discrepancies' => $discrepancies,
                'checked_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch discrepancies', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getReconciliationStatus(string $tenantId, string $periodId): array
    {
        $this->logger->debug('Fetching reconciliation status', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            $types = ['receivable', 'payable', 'asset'];
            $status = [];
            $allReconciled = true;

            foreach ($types as $type) {
                $subledgerData = $this->getSubledgerBalance($tenantId, $periodId, $type);
                $controlAccountCode = $this->getControlAccountCode($tenantId, $type);

                $glBalance = '0';
                if ($controlAccountCode !== null) {
                    $glData = $this->getGLBalance($tenantId, $periodId, $controlAccountCode);
                    $glBalance = $glData['balance'] ?? '0';
                }

                $subledgerBalance = $subledgerData['balance'] ?? '0';
                $variance = (string)((float) $subledgerBalance - (float) $glBalance);
                $isReconciled = (float) $variance === 0.0;

                if (!$isReconciled) {
                    $allReconciled = false;
                }

                $status[$type] = [
                    'is_reconciled' => $isReconciled,
                    'subledger_balance' => $subledgerBalance,
                    'gl_balance' => $glBalance,
                    'variance' => $variance,
                    'control_account' => $controlAccountCode,
                ];
            }

            return [
                'period_id' => $periodId,
                'all_reconciled' => $allReconciled,
                'details' => $status,
                'checked_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch reconciliation status', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get receivable subledger balance.
     */
    private function getReceivableBalance(string $tenantId, string $periodId): array
    {
        $balance = $this->receivableQuery->getTotalBalance($tenantId, $periodId);

        return [
            'balance' => $balance->getBalance(),
            'currency' => $balance->getCurrency(),
            'invoice_count' => $balance->getInvoiceCount(),
            'open_items' => $balance->getOpenItemCount(),
        ];
    }

    /**
     * Get payable subledger balance.
     */
    private function getPayableBalance(string $tenantId, string $periodId): array
    {
        $balance = $this->payableQuery->getTotalBalance($tenantId, $periodId);

        return [
            'balance' => $balance->getBalance(),
            'currency' => $balance->getCurrency(),
            'invoice_count' => $balance->getInvoiceCount(),
            'open_items' => $balance->getOpenItemCount(),
        ];
    }

    /**
     * Get asset subledger balance.
     */
    private function getAssetBalance(string $tenantId, string $periodId): array
    {
        $balance = $this->assetQuery->getNetBookValueTotal($tenantId, $periodId);

        return [
            'balance' => $balance->getNetBookValue(),
            'currency' => $balance->getCurrency(),
            'asset_count' => $balance->getAssetCount(),
            'accumulated_depreciation' => $balance->getAccumulatedDepreciation(),
        ];
    }

    /**
     * Get inventory subledger balance.
     *
     * Note: Inventory reconciliation requires integration with the Inventory package.
     * This method throws an exception to indicate the feature is not available
     * until an InventoryQueryInterface adapter is injected.
     *
     * @throws \RuntimeException When inventory query interface is not available
     */
    private function getInventoryBalance(string $tenantId, string $periodId): array
    {
        // Inventory reconciliation requires an inventory query adapter
        // The GLReconciliationProvider constructor accepts generic objects,
        // but inventory queries need to be explicitly handled
        throw new \RuntimeException(
            'Inventory reconciliation is not available. ' .
            'An InventoryQueryInterface adapter must be injected into GLReconciliationProvider ' .
            'to enable inventory subledger reconciliation.'
        );
    }

    /**
     * Get control account code for a subledger type.
     */
    private function getControlAccountCode(string $tenantId, string $subledgerType): ?string
    {
        try {
            return match ($subledgerType) {
                'receivable' => $this->receivableQuery->getControlAccountCode($tenantId),
                'payable' => $this->payableQuery->getControlAccountCode($tenantId),
                'asset' => $this->assetQuery->getControlAccountCode($tenantId),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get control account code', [
                'tenant_id' => $tenantId,
                'subledger_type' => $subledgerType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get detailed discrepancies for a subledger type.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDetailedDiscrepancies(string $tenantId, string $periodId, string $subledgerType): array
    {
        try {
            return match ($subledgerType) {
                'receivable' => $this->getReceivableDiscrepancies($tenantId, $periodId),
                'payable' => $this->getPayableDiscrepancies($tenantId, $periodId),
                'asset' => $this->getAssetDiscrepancies($tenantId, $periodId),
                default => [],
            };
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get detailed discrepancies', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'subledger_type' => $subledgerType,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get detailed discrepancies for receivables.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getReceivableDiscrepancies(string $tenantId, string $periodId): array
    {
        $unposted = $this->receivableQuery->getUnpostedTransactions($tenantId, $periodId);

        return array_map(fn($t) => [
            'type' => 'unposted_transaction',
            'reference' => $t->getReference(),
            'amount' => $t->getAmount(),
            'date' => $t->getDate()->format('Y-m-d'),
            'description' => $t->getDescription(),
        ], $unposted);
    }

    /**
     * Get detailed discrepancies for payables.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPayableDiscrepancies(string $tenantId, string $periodId): array
    {
        $unposted = $this->payableQuery->getUnpostedTransactions($tenantId, $periodId);

        return array_map(fn($t) => [
            'type' => 'unposted_transaction',
            'reference' => $t->getReference(),
            'amount' => $t->getAmount(),
            'date' => $t->getDate()->format('Y-m-d'),
            'description' => $t->getDescription(),
        ], $unposted);
    }

    /**
     * Get detailed discrepancies for assets.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAssetDiscrepancies(string $tenantId, string $periodId): array
    {
        $unposted = $this->assetQuery->getUnpostedDepreciation($tenantId, $periodId);

        return array_map(fn($d) => [
            'type' => 'unposted_depreciation',
            'asset_id' => $d->getAssetId(),
            'asset_code' => $d->getAssetCode(),
            'amount' => $d->getAmount(),
            'period' => $d->getPeriod(),
        ], $unposted);
    }
}
