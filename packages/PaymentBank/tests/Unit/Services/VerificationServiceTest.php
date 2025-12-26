<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\PaymentBank\Contracts\AccountVerificationInterface;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankProviderInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\DTOs\AccountVerificationResult;
use Nexus\PaymentBank\Entities\BankConnection;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;
use Nexus\PaymentBank\Enums\VerificationStatus;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Services\VerificationService;
use PHPUnit\Framework\TestCase;

final class VerificationServiceTest extends TestCase
{
    private BankConnectionQueryInterface $connectionQuery;
    private ProviderRegistryInterface $providerRegistry;
    private CryptoManagerInterface $crypto;
    private VerificationService $service;

    protected function setUp(): void
    {
        $this->connectionQuery = $this->createMock(BankConnectionQueryInterface::class);
        $this->providerRegistry = $this->createMock(ProviderRegistryInterface::class);
        $this->crypto = $this->createMock(CryptoManagerInterface::class);

        $this->service = new VerificationService(
            $this->connectionQuery,
            $this->providerRegistry,
            $this->crypto
        );
    }

    public function test_verify_ownership_calls_provider_verification(): void
    {
        $connectionId = 'conn-123';
        $accountId = 'acc-456';
        $identityData = [
            'name' => 'John Doe',
            'ssn_last_4' => '1234',
        ];

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $verifier = $this->createMock(AccountVerificationInterface::class);
        $expectedResult = new AccountVerificationResult(
            $accountId,
            VerificationStatus::VERIFIED,
            'verification-123',
            'John Doe'
        );

        $verifier->expects($this->once())
            ->method('verifyOwnership')
            ->with(
                $this->isType('array'),
                $accountId,
                $identityData
            )
            ->willReturn($expectedResult);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getAccountVerification')
            ->willReturn($verifier);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $result = $this->service->verifyOwnership($connectionId, $accountId, $identityData);

        $this->assertInstanceOf(AccountVerificationResult::class, $result);
        $this->assertTrue($result->isVerified());
        $this->assertEquals(VerificationStatus::VERIFIED, $result->getStatus());
    }

    public function test_verify_ownership_throws_exception_when_connection_not_found(): void
    {
        $this->expectException(BankConnectionNotFoundException::class);

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willReturn(null);

        $this->service->verifyOwnership('non-existent', 'acc-123', []);
    }

    public function test_initiate_micro_deposits_returns_verification_id(): void
    {
        $connectionId = 'conn-123';
        $accountId = 'acc-456';

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $verifier = $this->createMock(AccountVerificationInterface::class);
        $verifier->expects($this->once())
            ->method('initiateMicroDeposits')
            ->with(
                $this->isType('array'),
                $accountId
            )
            ->willReturn('verification-789');

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getAccountVerification')
            ->willReturn($verifier);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $verificationId = $this->service->initiateMicroDeposits($connectionId, $accountId);

        $this->assertEquals('verification-789', $verificationId);
    }

    public function test_initiate_micro_deposits_throws_exception_when_connection_not_found(): void
    {
        $this->expectException(BankConnectionNotFoundException::class);

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willReturn(null);

        $this->service->initiateMicroDeposits('non-existent', 'acc-123');
    }

    public function test_verify_micro_deposits_returns_true_when_successful(): void
    {
        $connectionId = 'conn-123';
        $verificationId = 'verification-789';
        $amounts = [0.32, 0.45];

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $verifier = $this->createMock(AccountVerificationInterface::class);
        $verifier->expects($this->once())
            ->method('verifyMicroDeposits')
            ->with(
                $this->isType('array'),
                $verificationId,
                $amounts
            )
            ->willReturn(true);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getAccountVerification')
            ->willReturn($verifier);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $result = $this->service->verifyMicroDeposits($connectionId, $verificationId, $amounts);

        $this->assertTrue($result);
    }

    public function test_verify_micro_deposits_throws_exception_when_connection_not_found(): void
    {
        $this->expectException(BankConnectionNotFoundException::class);

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willReturn(null);

        $this->service->verifyMicroDeposits('non-existent', 'verification-123', [0.32, 0.45]);
    }

    public function test_verify_micro_deposits_returns_false_when_verification_fails(): void
    {
        $connectionId = 'conn-123';
        $verificationId = 'verification-789';
        $amounts = [0.10, 0.20]; // Wrong amounts

        $connection = $this->createMockConnection();
        
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with($connectionId)
            ->willReturn($connection);

        $verifier = $this->createMock(AccountVerificationInterface::class);
        $verifier->expects($this->once())
            ->method('verifyMicroDeposits')
            ->willReturn(false);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('getAccountVerification')
            ->willReturn($verifier);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $result = $this->service->verifyMicroDeposits($connectionId, $verificationId, $amounts);

        $this->assertFalse($result);
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
