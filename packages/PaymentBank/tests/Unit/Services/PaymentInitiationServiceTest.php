<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankProviderInterface;
use Nexus\PaymentBank\Contracts\PaymentInitiationInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\DTOs\PaymentInitiationResult;
use Nexus\PaymentBank\Entities\BankConnection;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Services\PaymentInitiationService;
use Nexus\PaymentBank\ValueObjects\Beneficiary;
use PHPUnit\Framework\TestCase;

final class PaymentInitiationServiceTest extends TestCase
{
    private BankConnectionQueryInterface $connectionQuery;
    private ProviderRegistryInterface $providerRegistry;
    private PaymentInitiationService $service;

    protected function setUp(): void
    {
        $this->connectionQuery = $this->createMock(BankConnectionQueryInterface::class);
        $this->providerRegistry = $this->createMock(ProviderRegistryInterface::class);

        $this->service = new PaymentInitiationService(
            $this->connectionQuery,
            $this->providerRegistry
        );
    }

    public function test_initiate_payment_with_reference(): void
    {
        $connectionId = 'conn-123';
        $sourceAccountId = 'acc-456';
        $beneficiary = new Beneficiary(
            'John Doe',
            'GB82WEST12345698765432',
            'IBAN'
        );
        $amount = new Money(10000, 'USD');
        $reference = 'Invoice #12345';

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $paymentInitiator = $this->createMock(PaymentInitiationInterface::class);
        $expectedResult = new PaymentInitiationResult(
            'payment-789',
            'pending',
            'https://bank.com/authorize'
        );

        $paymentInitiator->expects($this->once())
            ->method('initiatePayment')
            ->with(
                $connection,
                $sourceAccountId,
                $beneficiary,
                $amount,
                $reference,
                []
            )
            ->willReturn($expectedResult);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getPaymentInitiation')
            ->willReturn($paymentInitiator);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $result = $this->service->initiatePayment(
            $connectionId,
            $sourceAccountId,
            $beneficiary,
            $amount,
            $reference
        );

        $this->assertInstanceOf(PaymentInitiationResult::class, $result);
        $this->assertEquals('payment-789', $result->paymentId);
        $this->assertEquals('pending', $result->status);
    }

    public function test_initiate_payment_without_reference_passes_null(): void
    {
        $connectionId = 'conn-123';
        $sourceAccountId = 'acc-456';
        $beneficiary = new Beneficiary(
            'Jane Smith',
            'US64SVBKUS6S3300958879',
            'IBAN'
        );
        $amount = new Money(5000, 'USD');

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $paymentInitiator = $this->createMock(PaymentInitiationInterface::class);
        $expectedResult = new PaymentInitiationResult(
            'payment-abc',
            'pending',
            null
        );

        // Verify that null is passed when reference is not provided
        $paymentInitiator->expects($this->once())
            ->method('initiatePayment')
            ->with(
                $connection,
                $sourceAccountId,
                $beneficiary,
                $amount,
                null, // Null is passed as-is to respect interface contract
                []
            )
            ->willReturn($expectedResult);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getPaymentInitiation')
            ->willReturn($paymentInitiator);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $result = $this->service->initiatePayment(
            $connectionId,
            $sourceAccountId,
            $beneficiary,
            $amount
        );

        $this->assertInstanceOf(PaymentInitiationResult::class, $result);
        $this->assertEquals('payment-abc', $result->paymentId);
    }

    public function test_initiate_payment_throws_exception_when_connection_not_found(): void
    {
        $this->expectException(BankConnectionNotFoundException::class);

        $connectionId = 'non-existent';
        $beneficiary = new Beneficiary('Test', 'GB82WEST12345698765432', 'IBAN');
        $amount = new Money(1000, 'USD');

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn(null);

        $this->service->initiatePayment(
            $connectionId,
            'acc-123',
            $beneficiary,
            $amount
        );
    }

    public function test_get_payment_status(): void
    {
        $connectionId = 'conn-123';
        $paymentId = 'payment-789';

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $paymentInitiator = $this->createMock(PaymentInitiationInterface::class);
        $paymentInitiator->expects($this->once())
            ->method('getPaymentStatus')
            ->with($connection, $paymentId)
            ->willReturn('completed');

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getPaymentInitiation')
            ->willReturn($paymentInitiator);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $status = $this->service->getPaymentStatus($connectionId, $paymentId);

        $this->assertEquals('completed', $status);
    }

    public function test_get_payment_status_throws_exception_when_connection_not_found(): void
    {
        $this->expectException(BankConnectionNotFoundException::class);

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willReturn(null);

        $this->service->getPaymentStatus('non-existent', 'payment-123');
    }

    private function createMockConnection(): BankConnectionInterface
    {
        return new BankConnection(
            'conn-123',
            'tenant-1',
            ProviderType::PLAID,
            'provider-conn-123',
            'encrypted_access_token',
            'encrypted_refresh_token',
            (new \DateTimeImmutable())->modify('+1 hour'),
            ConsentStatus::ACTIVE,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }
}
