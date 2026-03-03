<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Tests\Unit\Services;

use Nexus\ConnectivityOperations\Contracts\ConnectivityTelemetryPortInterface;
use Nexus\ConnectivityOperations\Contracts\FeatureFlagPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;
use Nexus\ConnectivityOperations\Contracts\SecretRotationPortInterface;
use Nexus\ConnectivityOperations\Coordinators\IntegrationGateway;
use Nexus\ConnectivityOperations\DataProviders\InMemoryProviderHealthStore;
use Nexus\ConnectivityOperations\DataProviders\ProviderHealthDataProvider;
use Nexus\ConnectivityOperations\Rules\ProviderCallRule;
use Nexus\ConnectivityOperations\Rules\ProviderFeatureFlagRule;
use Nexus\ConnectivityOperations\Workflows\IntegrationHealthWorkflow;
use Nexus\ConnectivityOperations\Workflows\ProviderCallWorkflow;
use Nexus\ConnectivityOperations\Workflows\SecretRotationWorkflow;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class IntegrationGatewayTest extends TestCase
{
    public function test_call_tracks_health_and_returns_response(): void
    {
        $healthStore = new InMemoryProviderHealthStore();
        $gateway = $this->createIntegrationGateway(
            healthStore: $healthStore,
            providerCallPort: new class implements ProviderCallPortInterface {
                public function call(string $providerId, string $endpoint, array $payload, array $options): array
                {
                    return ['ok' => true, 'provider' => $providerId, 'endpoint' => $endpoint];
                }
            },
            providerCatalogPort: new class implements ProviderCatalogPortInterface {
                public function providers(): array { return ['stripe']; }
                public function getConfig(string $providerId): array { return []; }
            }
        );

        $result = $gateway->call('stripe', 'https://api.stripe.test/charge', ['amount' => 100]);

        self::assertTrue($result['ok']);
        self::assertSame('healthy', $gateway->checkIntegrationsHealth()['stripe']);
    }

    public function test_rotate_secrets_returns_true_when_port_succeeds(): void
    {
        $healthStore = new InMemoryProviderHealthStore();
        $gateway = $this->createIntegrationGateway(
            healthStore: $healthStore,
            secretRotationPort: new class implements SecretRotationPortInterface {
                public function rotate(string $providerId): bool { return $providerId === 'twilio'; }
            },
            providerCatalogPort: new class implements ProviderCatalogPortInterface {
                public function providers(): array { return []; }
                public function getConfig(string $providerId): array { return []; }
            }
        );

        self::assertTrue($gateway->rotateSecrets('twilio'));
    }

    private function createIntegrationGateway(
        InMemoryProviderHealthStore $healthStore,
        ?ProviderCallPortInterface $providerCallPort = null,
        ?SecretRotationPortInterface $secretRotationPort = null,
        ?ProviderCatalogPortInterface $providerCatalogPort = null,
    ): IntegrationGateway {
        $providerCallPort ??= new class implements ProviderCallPortInterface {
            public function call(string $providerId, string $endpoint, array $payload, array $options): array
            {
                return ['status' => 'ok'];
            }
        };
        $secretRotationPort ??= new class implements SecretRotationPortInterface {
            public function rotate(string $providerId): bool { return true; }
        };
        $providerCatalogPort ??= new class implements ProviderCatalogPortInterface {
            public function providers(): array { return []; }
            public function getConfig(string $providerId): array { return []; }
        };

        $telemetryPort = new class implements ConnectivityTelemetryPortInterface {
            public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
            public function timing(string $metric, float $milliseconds, array $tags = []): void {}
        };

        return new IntegrationGateway(
            new ProviderCallWorkflow(
                new ProviderCallRule(),
                new ProviderFeatureFlagRule(new class implements FeatureFlagPortInterface {
                    public function isEnabled(string $flag, array $context = []): bool { return true; }
                }),
                $providerCallPort,
                $telemetryPort,
                $healthStore
            ),
            new SecretRotationWorkflow($secretRotationPort, $telemetryPort),
            new IntegrationHealthWorkflow(
                $providerCatalogPort,
                $providerCallPort,
                $healthStore,
                new ProviderHealthDataProvider($healthStore)
            ),
            new NullLogger()
        );
    }
}
