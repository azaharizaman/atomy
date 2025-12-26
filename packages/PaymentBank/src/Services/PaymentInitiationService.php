<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\PaymentInitiationServiceInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\DTOs\PaymentInitiationResult;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\ValueObjects\Beneficiary;

final readonly class PaymentInitiationService implements PaymentInitiationServiceInterface
{
    public function __construct(
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry
    ) {}

    public function initiatePayment(
        string $connectionId,
        string $sourceAccountId,
        Beneficiary $beneficiary,
        Money $amount,
        ?string $reference = null
    ): PaymentInitiationResult {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $initiator = $provider->getPaymentInitiation();
        
        // Provide default empty string if reference is null, as some providers may not handle null
        return $initiator->initiatePayment(
            $connection,
            $sourceAccountId,
            $beneficiary,
            $amount,
            $reference ?? '',
            []
        );
    }

    public function getPaymentStatus(string $connectionId, string $paymentId): string
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $initiator = $provider->getPaymentInitiation();
        
        return $initiator->getPaymentStatus($connection, $paymentId);
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }
}
