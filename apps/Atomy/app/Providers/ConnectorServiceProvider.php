<?php

declare(strict_types=1);

namespace App\Providers;

use App\Connectors\Adapters\{
    AwsSnsAdapter,
    MailchimpEmailAdapter,
    PayPalPaymentAdapter,
    SendGridEmailAdapter,
    StripePaymentAdapter,
    TwilioSmsAdapter
};
use App\Repositories\{
    CacheIdempotencyStore,
    DbIntegrationLogger,
    LaravelCredentialProvider,
    RedisCircuitBreakerStorage,
    RedisRateLimiterStorage
};
use App\Services\GuzzleHttpClient;
use Illuminate\Support\ServiceProvider;
use Nexus\Connector\Contracts\{
    CircuitBreakerStorageInterface,
    CredentialProviderInterface,
    EmailServiceConnectorInterface,
    HttpClientInterface,
    IdempotencyStoreInterface,
    IntegrationLoggerInterface,
    PaymentGatewayConnectorInterface,
    RateLimiterStorageInterface,
    SmsServiceConnectorInterface,
    WebhookVerifierInterface
};
use Nexus\Connector\Services\{ConnectorManager, RateLimiter, RetryHandler, WebhookVerifier};

class ConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register storage interfaces (REQUIRED for statelessness)
        $this->app->singleton(CircuitBreakerStorageInterface::class, RedisCircuitBreakerStorage::class);
        $this->app->singleton(RateLimiterStorageInterface::class, RedisRateLimiterStorage::class);
        $this->app->singleton(IdempotencyStoreInterface::class, CacheIdempotencyStore::class);
        
        // Register HTTP client
        $this->app->singleton(HttpClientInterface::class, GuzzleHttpClient::class);

        // Register core implementations
        $this->app->singleton(CredentialProviderInterface::class, LaravelCredentialProvider::class);
        $this->app->singleton(IntegrationLoggerInterface::class, DbIntegrationLogger::class);
        $this->app->singleton(WebhookVerifierInterface::class, WebhookVerifier::class);

        // Register core services
        $this->app->singleton(RateLimiter::class);
        $this->app->singleton(RetryHandler::class);
        $this->app->singleton(ConnectorManager::class);

        // Register email adapter based on configuration
        $this->app->singleton(EmailServiceConnectorInterface::class, function ($app) {
            $vendor = config('connector.email_vendor');
            $config = config("connector.email.{$vendor}");

            return match ($vendor) {
                'mailchimp' => new MailchimpEmailAdapter(
                    apiKey: $config['api_key'],
                    fromEmail: $config['from_email'],
                    fromName: $config['from_name']
                ),
                'sendgrid' => new SendGridEmailAdapter(
                    apiKey: $config['api_key'],
                    fromEmail: $config['from_email'],
                    fromName: $config['from_name']
                ),
                default => throw new \InvalidArgumentException("Unsupported email vendor: {$vendor}")
            };
        });

        // Register SMS adapter based on configuration
        $this->app->singleton(SmsServiceConnectorInterface::class, function ($app) {
            $vendor = config('connector.sms_vendor');
            $config = config("connector.sms.{$vendor}");

            return match ($vendor) {
                'twilio' => new TwilioSmsAdapter(
                    accountSid: $config['account_sid'],
                    authToken: $config['auth_token'],
                    fromNumber: $config['from_number']
                ),
                'aws_sns' => new AwsSnsAdapter(
                    accessKeyId: $config['key'],
                    secretAccessKey: $config['secret'],
                    region: $config['region']
                ),
                default => throw new \InvalidArgumentException("Unsupported SMS vendor: {$vendor}")
            };
        });

        // Register payment adapter based on configuration
        $this->app->singleton(PaymentGatewayConnectorInterface::class, function ($app) {
            $vendor = config('connector.payment_vendor');
            $config = config("connector.payment.{$vendor}");

            return match ($vendor) {
                'stripe' => new StripePaymentAdapter(
                    apiKey: $config['secret_key']
                ),
                'paypal' => new PayPalPaymentAdapter(
                    clientId: $config['client_id'],
                    clientSecret: $config['client_secret'],
                    sandbox: $config['mode'] === 'sandbox'
                ),
                default => throw new \InvalidArgumentException("Unsupported payment vendor: {$vendor}")
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/connector.php' => config_path('connector.php'),
            ], 'connector-config');
        }

        // Register scheduled task for log purging
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PurgeIntegrationLogs::class,
            ]);
        }
    }
}

