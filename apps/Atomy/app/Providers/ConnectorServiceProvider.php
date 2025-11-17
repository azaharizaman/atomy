<?php

declare(strict_types=1);

namespace App\Providers;

use App\Connectors\Adapters\{MailchimpEmailAdapter, SendGridEmailAdapter, TwilioSmsAdapter};
use App\Repositories\{DbIntegrationLogger, LaravelCredentialProvider};
use Illuminate\Support\ServiceProvider;
use Nexus\Connector\Contracts\{
    CredentialProviderInterface,
    EmailServiceConnectorInterface,
    IntegrationLoggerInterface,
    SmsServiceConnectorInterface,
    WebhookVerifierInterface
};
use Nexus\Connector\Services\{ConnectorManager, RetryHandler, WebhookVerifier};

class ConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register core implementations
        $this->app->singleton(CredentialProviderInterface::class, LaravelCredentialProvider::class);
        $this->app->singleton(IntegrationLoggerInterface::class, DbIntegrationLogger::class);
        $this->app->singleton(WebhookVerifierInterface::class, WebhookVerifier::class);

        // Register core services
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
                default => throw new \InvalidArgumentException("Unsupported SMS vendor: {$vendor}")
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
