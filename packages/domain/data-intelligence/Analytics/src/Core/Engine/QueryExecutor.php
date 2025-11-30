<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Engine;

use Nexus\Analytics\Core\Contracts\QueryExecutorInterface;
use Nexus\Analytics\Core\Contracts\TransactionManagerInterface;
use Nexus\Analytics\Contracts\QueryDefinitionInterface;
use Nexus\Analytics\Contracts\QueryResultInterface;
use Nexus\Analytics\Contracts\AnalyticsContextInterface;
use Nexus\Analytics\ValueObjects\QueryResult;
use Nexus\Analytics\Exceptions\QueryExecutionException;
use Nexus\Analytics\Exceptions\GuardConditionFailedException;

/**
 * Internal query execution engine
 */
final readonly class QueryExecutor implements QueryExecutorInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private GuardEvaluator $guardEvaluator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(QueryDefinitionInterface $query, AnalyticsContextInterface $context): QueryResultInterface
    {
        $startTime = hrtime(true);

        try {
            // Validate guards
            $this->validateGuards($query, $context);

            // Execute query (potentially in transaction)
            if ($query->requiresTransaction()) {
                $data = $this->transactionManager->executeInTransaction(
                    fn() => $this->executeQueryLogic($query, $context)
                );
            } else {
                $data = $this->executeQueryLogic($query, $context);
            }

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return QueryResult::success($query->getId(), $data, $durationMs);
        } catch (GuardConditionFailedException $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            // Return failure result to maintain audit trail consistency
            return QueryResult::failure($query->getId(), $e->getMessage(), $durationMs);
        } catch (\Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            
            // Attempt compensation if transaction failed
            if ($query->requiresTransaction()) {
                $this->transactionManager->compensate($query->getId(), [
                    'error' => $e->getMessage(),
                    'context' => $context->getContextData(),
                ]);
            }

            return QueryResult::failure($query->getId(), $e->getMessage(), $durationMs);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateGuards(QueryDefinitionInterface $query, AnalyticsContextInterface $context): bool
    {
        $guards = $query->getGuards();

        if (empty($guards)) {
            return true;
        }

        return $this->guardEvaluator->evaluateAll($guards, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function executeWithRetry(
        QueryDefinitionInterface $query,
        AnalyticsContextInterface $context,
        int $maxRetries = 3
    ): QueryResultInterface {
        $lastException = null;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $this->execute($query, $context);
            } catch (GuardConditionFailedException $e) {
                // Don't retry guard failures
                throw $e;
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $maxRetries) {
                    // Exponential backoff
                    usleep((int) (100_000 * pow(2, $attempt - 1)));
                }
            }
        }

        // All retries exhausted
        $errorMessage = $lastException ? $lastException->getMessage() : 'Unknown error';
        throw new QueryExecutionException(
            $query->getId(),
            "Failed after {$maxRetries} retries: {$errorMessage}",
            $lastException
        );
    }

    /**
     * Execute the actual query logic
     *
     * @param QueryDefinitionInterface $query
     * @param AnalyticsContextInterface $context
     * @return array<string, mixed>
     */
    private function executeQueryLogic(QueryDefinitionInterface $query, AnalyticsContextInterface $context): array
    {
        // This is where the actual query execution would happen
        // In a real implementation, this would delegate to specific query handlers
        // based on query type (aggregation, prediction, report, etc.)
        
        // For now, return a placeholder structure
        return [
            'query_id' => $query->getId(),
            'query_name' => $query->getName(),
            'query_type' => $query->getType(),
            'executed' => true,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }
}
