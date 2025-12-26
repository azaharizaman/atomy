<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\Common\Contracts\UlidInterface;
use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\PaymentBank\Contracts\BankConnectionPersistInterface;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankProviderInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankConnection;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Services\BankConnectionManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BankConnectionManagerTest extends TestCase
{
    private BankConnectionPersistInterface $persist;
    private BankConnectionQueryInterface $query;
    private ProviderRegistryInterface $providerRegistry;
    private CryptoManagerInterface $crypto;
    private LoggerInterface $logger;
    private UlidInterface $ulid;
    private BankConnectionManager $manager;

    protected function setUp(): void
    {
        $this->persist = $this->createMock(BankConnectionPersistInterface::class);
        $this->query = $this->createMock(BankConnectionQueryInterface::class);
        $this->providerRegistry = $this->createMock(ProviderRegistryInterface::class);
        $this->crypto = $this->createMock(CryptoManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ulid = $this->createMock(UlidInterface::class);

        $this->manager = new BankConnectionManager(
            $this->persist,
            $this->query,
            $this->providerRegistry,
            $this->crypto,
            $this->logger,
            $this->ulid
        );
    }

    public function test_initiate_connection_returns_config(): void
    {
        $provider = $this->createMock(BankProviderInterface::class);
        $this->providerRegistry->expects($this->once())
            ->method('getProvider')
            ->with(ProviderType::PLAID)
            ->willReturn($provider);

        $result = $this->manager->initiateConnection('plaid', 'tenant-1');

        $this->assertIsArray($result);
        $this->assertEquals('plaid', $result['provider']);
        $this->assertEquals('tenant-1', $result['tenant_id']);
        $this->assertStringContainsString('https://auth.plaid.com', $result['url']);
    }

    public function test_complete_connection_saves_connection(): void
    {
        $provider = $this->createMock(BankProviderInterface::class);
        $this->providerRegistry->expects($this->once())
            ->method('getProvider')
            ->with(ProviderType::PLAID)
            ->willReturn($provider);

        // Mock ULID generation
        $this->ulid->expects($this->once())
            ->method('generate')
            ->willReturn('01HQWXYZ1234567890ABCDEFGH');

        // Mock crypto encrypt to return EncryptedData
        $encryptedData = new EncryptedData(
            'base64ciphertext',
            'base64iv',
            'base64tag',
            SymmetricAlgorithm::AES256GCM
        );
        
        $this->crypto->expects($this->exactly(2))
            ->method('encrypt')
            ->willReturn($encryptedData);

        $this->persist->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (BankConnectionInterface $connection) {
                return $connection;
            });

        $result = $this->manager->completeConnection('plaid', 'tenant-1', ['institution_id' => 'ins_123']);

        $this->assertInstanceOf(BankConnectionInterface::class, $result);
        $this->assertEquals(ProviderType::PLAID, $result->getProviderType());
        $this->assertEquals('tenant-1', $result->getTenantId());
        $this->assertEquals(ConsentStatus::ACTIVE, $result->getConsentStatus());
        $this->assertEquals('01HQWXYZ1234567890ABCDEFGH', $result->getId());
    }

    public function test_refresh_connection_updates_credentials(): void
    {
        $encryptedData = new EncryptedData(
            'base64ciphertext',
            'base64iv',
            'base64tag',
            SymmetricAlgorithm::AES256GCM
        );
        
        $connection = new BankConnection(
            id: 'conn-1',
            tenantId: 'tenant-1',
            providerType: ProviderType::PLAID,
            providerConnectionId: 'ins_123',
            accessToken: $encryptedData->toJson(),
            refreshToken: $encryptedData->toJson(),
            expiresAt: null,
            consentStatus: ConsentStatus::ACTIVE,
            metadata: [],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $this->query->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn($connection);

        $provider = $this->createMock(BankProviderInterface::class);
        $this->providerRegistry->expects($this->once())
            ->method('getProvider')
            ->with(ProviderType::PLAID)
            ->willReturn($provider);

        // Mock crypto decrypt
        $this->crypto->expects($this->exactly(2))
            ->method('decrypt')
            ->willReturn('decrypted_token');
        
        // Mock crypto encrypt for new token
        $this->crypto->expects($this->once())
            ->method('encrypt')
            ->willReturn($encryptedData);

        $this->persist->expects($this->once())
            ->method('save')
            ->willReturnArgument(0);

        $result = $this->manager->refreshConnection('conn-1');

        $this->assertInstanceOf(BankConnectionInterface::class, $result);
        // Token should be encrypted (JSON format)
        $this->assertStringContainsString('ciphertext', $result->getAccessToken());
    }

    public function test_disconnect_deletes_connection(): void
    {
        $connection = new BankConnection(
            id: 'conn-1',
            tenantId: 'tenant-1',
            providerType: ProviderType::PLAID,
            providerConnectionId: 'ins_123',
            accessToken: 'encrypted_token',
            refreshToken: null,
            expiresAt: null,
            consentStatus: ConsentStatus::ACTIVE,
            metadata: [],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $this->query->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn($connection);

        $this->providerRegistry->expects($this->once())
            ->method('getProvider')
            ->with(ProviderType::PLAID)
            ->willReturn($this->createMock(BankProviderInterface::class));

        $this->persist->expects($this->once())
            ->method('delete')
            ->with('conn-1');

        $this->manager->disconnect('conn-1');
    }

    public function test_disconnect_throws_exception_if_not_found(): void
    {
        $this->query->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn(null);

        $this->expectException(BankConnectionNotFoundException::class);

        $this->manager->disconnect('conn-1');
    }
}
