<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

/**
 * Budget Transaction Repository contract
 * 
 * Combined interface for budget transaction operations.
 */
interface BudgetTransactionRepositoryInterface extends BudgetTransactionQueryInterface, BudgetTransactionPersistInterface
{
}
