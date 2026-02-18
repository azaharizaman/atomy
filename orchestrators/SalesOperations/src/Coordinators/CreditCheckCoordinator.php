<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Coordinators;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\DTOs\CreditCheckResult;
use Psr\Log\LoggerInterface;

final readonly class CreditCheckCoordinator
{
    public function __construct(
        private CreditManagerInterface $creditManager,
        private CustomerProviderInterface $customerProvider,
        private LoggerInterface $logger
    ) {}

    public function checkCredit(CreditCheckRequest $request): CreditCheckResult
    {
        $customer = $this->customerProvider->findById(
            $request->tenantId,
            $request->customerId
        );

        if ($customer === null) {
            return new CreditCheckResult(
                approved: false,
                creditLimit: 0.0,
                currentUsage: 0.0,
                availableCredit: 0.0,
                requestedAmount: $request->orderAmount,
                reason: "Customer not found"
            );
        }

        $checkResult = $this->creditManager->checkCreditLimit(
            $request->tenantId,
            $request->customerId,
            $request->orderAmount
        );

        $this->logger->info(
            sprintf(
                "Credit check for customer %s: %s (Limit: %.2f, Usage: %.2f, Requested: %.2f)",
                $request->customerId,
                $checkResult->isApproved() ? 'APPROVED' : 'DENIED',
                $checkResult->getCreditLimit(),
                $checkResult->getCurrentUsage(),
                $request->orderAmount
            )
        );

        return new CreditCheckResult(
            approved: $checkResult->isApproved(),
            creditLimit: $checkResult->getCreditLimit(),
            currentUsage: $checkResult->getCurrentUsage(),
            availableCredit: $checkResult->getAvailableCredit(),
            requestedAmount: $request->orderAmount,
            reason: $checkResult->getReason(),
            requiresManagerApproval: !$checkResult->isApproved() && $request->orderAmount <= $checkResult->getCreditLimit() * 1.1
        );
    }

    public function reserveCredit(string $tenantId, string $customerId, string $orderId, float $amount): bool
    {
        return $this->creditManager->reserveCredit($tenantId, $customerId, $orderId, $amount);
    }

    public function releaseCredit(string $tenantId, string $orderId): void
    {
        $this->creditManager->releaseCredit($tenantId, $orderId);
    }
}
