<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Exceptions\CreditLimitExceededException;
use Psr\Log\LoggerInterface;

/**
 * Credit Limit Checker Service
 *
 * Validates customer credit limits (individual and group).
 */
final readonly class CreditLimitChecker implements CreditLimitCheckerInterface
{
    public function __construct(
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private PartyRepositoryInterface $partyRepository,
        private LoggerInterface $logger
    ) {}

    public function checkCreditLimit(string $customerId, float $newOrderAmount, string $currency): void
    {
        $customer = $this->partyRepository->findById($customerId);
        
        if ($customer === null) {
            throw new \InvalidArgumentException("Customer {$customerId} not found");
        }

        // Check individual credit limit
        $creditLimit = $customer->getCreditLimit();
        
        if ($creditLimit !== null && $creditLimit > 0) {
            $outstandingBalance = $this->invoiceRepository->getOutstandingBalance($customerId);
            $projectedBalance = $outstandingBalance + $newOrderAmount;

            if ($projectedBalance > $creditLimit) {
                $this->logger->warning('Credit limit exceeded for customer', [
                    'customer_id' => $customerId,
                    'credit_limit' => $creditLimit,
                    'outstanding_balance' => $outstandingBalance,
                    'new_order_amount' => $newOrderAmount,
                    'projected_balance' => $projectedBalance,
                ]);

                throw CreditLimitExceededException::individualLimitExceeded(
                    $customerId,
                    $creditLimit,
                    $projectedBalance
                );
            }
        }

        // Check group credit limit if customer belongs to a group
        $customerGroupId = $customer->getCustomerGroupId();
        
        if ($customerGroupId !== null) {
            $this->checkGroupCreditLimit($customerGroupId, $customerId, $newOrderAmount, $currency);
        }
    }

    public function checkGroupCreditLimit(
        string $customerGroupId,
        string $customerId,
        float $newOrderAmount,
        string $currency
    ): void {
        $customerGroup = $this->partyRepository->findById($customerGroupId);
        
        if ($customerGroup === null) {
            throw new \InvalidArgumentException("Customer group {$customerGroupId} not found");
        }

        $groupCreditLimit = $customerGroup->getCreditLimit();
        
        if ($groupCreditLimit === null || $groupCreditLimit <= 0) {
            return; // No group limit set
        }

        $groupOutstandingBalance = $this->invoiceRepository->getGroupOutstandingBalance($customerGroupId);
        $projectedGroupBalance = $groupOutstandingBalance + $newOrderAmount;

        if ($projectedGroupBalance > $groupCreditLimit) {
            $this->logger->warning('Group credit limit exceeded', [
                'customer_group_id' => $customerGroupId,
                'customer_id' => $customerId,
                'group_credit_limit' => $groupCreditLimit,
                'group_outstanding_balance' => $groupOutstandingBalance,
                'new_order_amount' => $newOrderAmount,
                'projected_group_balance' => $projectedGroupBalance,
            ]);

            throw CreditLimitExceededException::groupLimitExceeded(
                $customerGroupId,
                $groupCreditLimit,
                $projectedGroupBalance
            );
        }
    }

    public function getAvailableCredit(string $customerId): ?float
    {
        $customer = $this->partyRepository->findById($customerId);
        
        if ($customer === null) {
            return null;
        }

        $creditLimit = $customer->getCreditLimit();
        
        if ($creditLimit === null || $creditLimit <= 0) {
            return null; // No limit set = unlimited credit
        }

        $outstandingBalance = $this->invoiceRepository->getOutstandingBalance($customerId);
        
        return max(0.0, $creditLimit - $outstandingBalance);
    }

    public function getGroupAvailableCredit(string $customerGroupId): ?float
    {
        $customerGroup = $this->partyRepository->findById($customerGroupId);
        
        if ($customerGroup === null) {
            return null;
        }

        $groupCreditLimit = $customerGroup->getCreditLimit();
        
        if ($groupCreditLimit === null || $groupCreditLimit <= 0) {
            return null; // No limit set = unlimited credit
        }

        $groupOutstandingBalance = $this->invoiceRepository->getGroupOutstandingBalance($customerGroupId);
        
        return max(0.0, $groupCreditLimit - $groupOutstandingBalance);
    }

    public function isCreditLimitExceeded(string $customerId): bool
    {
        $availableCredit = $this->getAvailableCredit($customerId);
        
        // If no limit set, credit is never exceeded
        if ($availableCredit === null) {
            return false;
        }

        return $availableCredit <= 0;
    }

    public function isGroupCreditLimitExceeded(string $customerGroupId): bool
    {
        $availableCredit = $this->getGroupAvailableCredit($customerGroupId);
        
        // If no limit set, credit is never exceeded
        if ($availableCredit === null) {
            return false;
        }

        return $availableCredit <= 0;
    }
}
