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

        $gateway = new IntegrationGateway(
            new ProviderCallWorkflow(
                new ProviderCallRule(),
                new ProviderFeatureFlagRule(new class implements FeatureFlagPortInterface {
                    public function isEnabled(string $flag, array $context = []): bool { return true; }
                }),
                new class implements ProviderCallPortInterface {
                    public function call(string $providerId, string $endpoint, array $payload, array $options): array
                    {
                        return ['ok' => true, 'provider' => $providerId, 'endpoint' => $endpoint];
                    }
                },
                new class implements ConnectivityTelemetryPortInterface {
                    public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
                    public function timing(string $metric, float $milliseconds, array $tags = []): void {}
                },
                $healthStore
            ),
            new SecretRotationWorkflow(
                new class implements SecretRotationPortInterface {
                    public function rotate(string $providerId): bool { return true; }
                },
                new class implements ConnectivityTelemetryPortInterface {
                    public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
                    public function timing(string $metric, float $milliseconds, array $tags = []): void {}
                }
            ),
            new IntegrationHealthWorkflow(
                new class implements ProviderCatalogPortInterface {
                    public function providers(): array { return ['stripe']; }
                },
                new class implements ProviderCallPortInterface {
                    public function call(string $providerId, string $endpoint, array $payload, array $options): array
                    {
                        return ['status' => 'ok'];
                    }
                },
                $healthStore,
                new ProviderHealthDataProvider($healthStore)
            ),
            new NullLogger()
        );

        $result = $gateway->call('stripe', 'https://api.stripe.test/charge', ['amount' => 100]);

        self::assertTrue($result['ok']);
        self::assertSame('healthy', $gateway->checkIntegrationsHealth()['stripe']);
    }

    public function test_rotate_secrets_returns_true_when_port_succeeds(): void
    {
        $healthStore = new InMemoryProviderHealthStore();

        $gateway = new IntegrationGateway(
            new ProviderCallWorkflow(
                new ProviderCallRule(),
                new ProviderFeatureFlagRule(new class implements FeatureFlagPortInterface {
                    public function isEnabled(string $flag, array $context = []): bool { return true; }
                }),
                new class implements ProviderCallPortInterface {
                    public function call(string $providerId, string $endpoint, array $payload, array $options): array
                    {
                        return ['status' => 'ok'];
                    }
                },
                new class implements ConnectivityTelemetryPortInterface {
                    public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
                    public function timing(string $metric, float $milliseconds, array $tags = []): void {}
                },
                $healthStore
            ),
            new SecretRotationWorkflow(
                new class implements SecretRotationPortInterface {
                    public function rotate(string $providerId): bool { return $providerId === 'twilio'; }
                },
                new class implements ConnectivityTelemetryPortInterface {
                    public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
                    public function timing(string $metric, float $milliseconds, array $tags = []): void {}
                }
            ),
            new IntegrationHealthWorkflow(
                new class implements ProviderCatalogPortInterface {
                    public function providers(): array { return []; }
                },
                new class implements ProviderCallPortInterface {
                    public function call(string $providerId, string $endpoint, array $payload, array $options): array { return []; }
                },
                $healthStore,
                new ProviderHealthDataProvider($healthStore)
            ),
            new NullLogger()
        );

        self::assertTrue($gateway->rotateSecrets('twilio'));
    }
}
