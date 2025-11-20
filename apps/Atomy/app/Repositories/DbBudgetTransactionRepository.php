<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BudgetTransaction;
use Illuminate\Support\Str;
use Nexus\Budget\Contracts\BudgetTransactionInterface;
use Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Currency\ValueObjects\Money;

/**
 * Database Budget Transaction Repository
 * 
 * Laravel/Eloquent implementation of BudgetTransactionRepositoryInterface.
 */
final class DbBudgetTransactionRepository implements BudgetTransactionRepositoryInterface
{
    public function __construct(
        private readonly BudgetTransaction $model
    ) {}

    public function findById(string $id): ?BudgetTransactionInterface
    {
        return $this->model->find($id);
    }

    public function findByBudget(string $budgetId): array
    {
        return $this->model
            ->where('budget_id', $budgetId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findBySourceDocument(string $sourceDocumentId): array
    {
        return $this->model
            ->where('source_document_id', $sourceDocumentId)
            ->get()
            ->all();
    }

    public function recordCommitment(
        string $budgetId,
        Money $amount,
        string $sourceDocumentId,
        TransactionType $transactionType
    ): BudgetTransactionInterface {
        $transaction = $this->model->create([
            'id' => Str::ulid()->toString(),
            'budget_id' => $budgetId,
            'transaction_type' => TransactionType::Commitment->value,
            'source_document_id' => $sourceDocumentId,
            'source_document_type' => $this->inferDocumentType($sourceDocumentId),
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
            'is_released' => false,
            'is_reversal' => false,
            'created_by' => null, // Would come from auth context
        ]);

        // Update budget committed_amount
        \DB::table('budgets')
            ->where('id', $budgetId)
            ->increment('committed_amount', $amount->getAmount());

        return $transaction;
    }

    public function releaseCommitment(string $transactionId): void
    {
        $transaction = $this->model->find($transactionId);
        if (!$transaction || $transaction->is_released) {
            return;
        }

        $transaction->update([
            'is_released' => true,
            'released_at' => now(),
            'released_by' => null, // Would come from auth context
        ]);

        // Update budget committed_amount
        \DB::table('budgets')
            ->where('id', $transaction->budget_id)
            ->decrement('committed_amount', $transaction->amount);
    }

    public function recordActual(
        string $budgetId,
        Money $amount,
        string $sourceDocumentId,
        TransactionType $transactionType
    ): BudgetTransactionInterface {
        $transaction = $this->model->create([
            'id' => Str::ulid()->toString(),
            'budget_id' => $budgetId,
            'transaction_type' => TransactionType::Actual->value,
            'source_document_id' => $sourceDocumentId,
            'source_document_type' => $this->inferDocumentType($sourceDocumentId),
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
            'is_released' => false,
            'is_reversal' => false,
            'created_by' => null, // Would come from auth context
        ]);

        // Update budget actual_amount
        \DB::table('budgets')
            ->where('id', $budgetId)
            ->increment('actual_amount', $amount->getAmount());

        return $transaction;
    }

    public function reverseTransaction(string $transactionId): BudgetTransactionInterface
    {
        $original = $this->model->find($transactionId);
        if (!$original) {
            throw new \InvalidArgumentException("Transaction not found: {$transactionId}");
        }

        $reversal = $this->model->create([
            'id' => Str::ulid()->toString(),
            'budget_id' => $original->budget_id,
            'transaction_type' => $original->transaction_type->value,
            'source_document_id' => $original->source_document_id,
            'source_document_type' => $original->source_document_type,
            'amount' => -$original->amount, // Negative amount for reversal
            'currency' => $original->currency,
            'is_released' => false,
            'is_reversal' => true,
            'reversed_by_transaction_id' => $transactionId,
            'created_by' => null, // Would come from auth context
        ]);

        // Update budget amounts
        if ($original->transaction_type === TransactionType::Commitment) {
            \DB::table('budgets')
                ->where('id', $original->budget_id)
                ->decrement('committed_amount', $original->amount);
        } elseif ($original->transaction_type === TransactionType::Actual) {
            \DB::table('budgets')
                ->where('id', $original->budget_id)
                ->decrement('actual_amount', $original->amount);
        }

        return $reversal;
    }

    public function getTotalCommitments(string $budgetId): Money
    {
        $total = $this->model
            ->where('budget_id', $budgetId)
            ->where('transaction_type', TransactionType::Commitment)
            ->where('is_released', false)
            ->sum('amount');

        // Get currency from first transaction or default to 'MYR'
        $currency = $this->model
            ->where('budget_id', $budgetId)
            ->value('currency') ?? 'MYR';

        return Money::of($total, $currency);
    }

    public function getTotalActuals(string $budgetId): Money
    {
        $total = $this->model
            ->where('budget_id', $budgetId)
            ->where('transaction_type', TransactionType::Actual)
            ->sum('amount');

        // Get currency from first transaction or default to 'MYR'
        $currency = $this->model
            ->where('budget_id', $budgetId)
            ->value('currency') ?? 'MYR';

        return Money::of($total, $currency);
    }

    /**
     * Infer document type from source document ID prefix
     */
    private function inferDocumentType(string $sourceDocumentId): string
    {
        // Simple heuristic based on ID prefix
        if (str_starts_with($sourceDocumentId, 'PO-')) {
            return 'purchase_order';
        } elseif (str_starts_with($sourceDocumentId, 'JE-')) {
            return 'journal_entry';
        } elseif (str_starts_with($sourceDocumentId, 'INV-')) {
            return 'invoice';
        }

        return 'unknown';
    }
}
