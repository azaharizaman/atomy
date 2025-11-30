<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Connector
 * 
 * This example demonstrates:
 * 1. Custom endpoint configuration with retry and rate limiting
 * 2. Multi-tenant credential isolation
 * 3. Webhook verification
 * 4. OAuth token refresh
 * 5. Cloud storage with signed URLs
 */

use Nexus\Connector\Contracts\CloudStorageConnectorInterface;
use Nexus\Connector\Contracts\CredentialProviderInterface;
use Nexus\Connector\Contracts\WebhookVerifierInterface;
use Nexus\Connector\ValueObjects\Endpoint;
use Nexus\Connector\ValueObjects\HttpMethod;
use Nexus\Connector\ValueObjects\RetryPolicy;
use Nexus\Connector\ValueObjects\RateLimitConfig;

// ============================================
// Step 1: Custom Endpoint with Advanced Config
// ============================================

final readonly class CustomApiIntegration
{
    public function createEndpoint(): Endpoint
    {
        return Endpoint::create(
            url: 'https://api.vendor.com/v2/resource',
            method: HttpMethod::POST
        )
        ->withTimeout(30)
        ->withRetryPolicy(
            RetryPolicy::create(
                maxAttempts: 5,
                initialDelayMs: 500,
                multiplier: 2.0,
                maxDelayMs: 30000
            )
        )
        ->withRateLimit(
            RateLimitConfig::perSecond(50) // 50 req/sec vendor limit
        );
    }
}

// Usage
// $integration = new CustomApiIntegration();
// $endpoint = $integration->createEndpoint();
// // Use endpoint with ConnectorManager...

// ============================================
// Step 2: Multi-Tenant Credential Management
// ============================================

final readonly class MultiTenantIntegration
{
    public function __construct(
        private CredentialProviderInterface $credentialProvider
    ) {}
    
    public function getCredentialsForTenant(string $tenantId, string $serviceName): array
    {
        // Credentials are automatically isolated by tenant
        $credentials = $this->credentialProvider->getCredentials(
            serviceName: $serviceName,
            tenantId: $tenantId
        );
        
        return [
            'auth_method' => $credentials->authMethod->value,
            'has_credentials' => true,
        ];
    }
    
    public function hasCredentials(string $tenantId, string $serviceName): bool
    {
        return $this->credentialProvider->hasCredentials(
            serviceName: $serviceName,
            tenantId: $tenantId
        );
    }
}

// Usage
// $service = new MultiTenantIntegration($credentialProvider);
// $hasStripe = $service->hasCredentials('tenant-123', 'stripe');
// if ($hasStripe) {
//     $creds = $service->getCredentialsForTenant('tenant-123', 'stripe');
//     echo "Auth method: {$creds['auth_method']}\n";
// }

// ============================================
// Step 3: Webhook Signature Verification
// ============================================

final readonly class WebhookHandler
{
    public function __construct(
        private WebhookVerifierInterface $verifier
    ) {}
    
    public function handleStripeWebhook(string $payload, string $signature, string $secret): array
    {
        // Verify webhook signature
        if (!$this->verifier->verify($payload, $signature, $secret)) {
            return [
                'success' => false,
                'error' => 'Invalid webhook signature - possible spoofing attempt'
            ];
        }
        
        // Signature verified - safe to process
        $event = json_decode($payload, true);
        
        // Process webhook event
        // ... handle payment success, refund, etc.
        
        return [
            'success' => true,
            'event_type' => $event['type'] ?? 'unknown'
        ];
    }
}

// Usage (in webhook controller)
// $handler = new WebhookHandler($webhookVerifier);
// $result = $handler->handleStripeWebhook(
//     payload: $request->getContent(),
//     signature: $request->header('Stripe-Signature'),
//     secret: config('services.stripe.webhook_secret')
// );

// ============================================
// Step 4: OAuth Token Refresh
// ============================================

use Nexus\Connector\Exceptions\CredentialRefreshException;

final readonly class OAuthIntegration
{
    public function __construct(
        private CredentialProviderInterface $credentialProvider
    ) {}
    
    public function ensureFreshToken(string $serviceName, string $tenantId): array
    {
        try {
            // Get current credentials
            $credentials = $this->credentialProvider->getCredentials($serviceName, $tenantId);
            
            // Check if token is expired or about to expire
            // If expired, refresh automatically
            $refreshed = $this->credentialProvider->refreshCredentials($serviceName, $tenantId);
            
            return [
                'success' => true,
                'token_refreshed' => true,
                'auth_method' => $refreshed->authMethod->value
            ];
        } catch (CredentialRefreshException $e) {
            return [
                'success' => false,
                'error' => 'Token refresh failed: ' . $e->getMessage(),
                'action_required' => 'User must re-authenticate'
            ];
        }
    }
}

// Usage
// $integration = new OAuthIntegration($credentialProvider);
// $result = $integration->ensureFreshToken('google', 'tenant-123');
// if (!$result['success']) {
//     // Redirect user to OAuth authorization flow
// }

// ============================================
// Step 5: Cloud Storage with Signed URLs
// ============================================

final readonly class SecureFileManager
{
    public function __construct(
        private CloudStorageConnectorInterface $cloudStorage
    ) {}
    
    public function uploadSecureFile(string $filePath, string $contents): string
    {
        // Upload file with private ACL
        $url = $this->cloudStorage->upload(
            path: $filePath,
            contents: $contents,
            options: [
                'content_type' => 'application/pdf',
                'acl' => 'private', // Not publicly accessible
                'metadata' => [
                    'uploaded_by' => 'system',
                    'uploaded_at' => date('Y-m-d H:i:s')
                ]
            ]
        );
        
        return $url;
    }
    
    public function generateTemporaryAccessUrl(string $filePath, int $validForMinutes = 60): string
    {
        // Generate signed URL valid for specified duration
        return $this->cloudStorage->generateSignedUrl(
            path: $filePath,
            expiresInSeconds: $validForMinutes * 60
        );
    }
    
    public function downloadFile(string $filePath): string
    {
        return $this->cloudStorage->download($filePath);
    }
    
    public function deleteFile(string $filePath): bool
    {
        return $this->cloudStorage->delete($filePath);
    }
}

// Usage
// $manager = new SecureFileManager($cloudStorage);
// 
// // Upload private file
// $url = $manager->uploadSecureFile(
//     'contracts/2025/contract-001.pdf',
//     file_get_contents('/tmp/contract.pdf')
// );
// 
// // Generate temporary access URL (valid for 24 hours)
// $signedUrl = $manager->generateTemporaryAccessUrl('contracts/2025/contract-001.pdf', 1440);
// // Send $signedUrl to customer via email - expires in 24 hours
// 
// // Download file
// $contents = $manager->downloadFile('contracts/2025/contract-001.pdf');
// 
// // Delete file
// $deleted = $manager->deleteFile('contracts/2025/contract-001.pdf');

// ============================================
// Step 6: Integration Metrics Dashboard
// ============================================

use Nexus\Connector\Contracts\IntegrationLoggerInterface;

final readonly class MetricsDashboard
{
    public function __construct(
        private IntegrationLoggerInterface $logger
    ) {}
    
    public function getServiceHealth(string $serviceName, int $daysBack = 7): array
    {
        $from = new \DateTimeImmutable("-{$daysBack} days");
        $to = new \DateTimeImmutable('now');
        
        $metrics = $this->logger->getMetrics($serviceName, $from, $to);
        
        // Determine health status
        $health = match(true) {
            $metrics['success_rate'] >= 99 => 'excellent',
            $metrics['success_rate'] >= 95 => 'good',
            $metrics['success_rate'] >= 90 => 'degraded',
            default => 'critical'
        };
        
        return [
            'service' => $serviceName,
            'period_days' => $daysBack,
            'health_status' => $health,
            'metrics' => $metrics
        ];
    }
    
    public function getAllServicesHealth(): array
    {
        $services = ['mailchimp', 'twilio', 'stripe', 's3'];
        $health = [];
        
        foreach ($services as $service) {
            $health[$service] = $this->getServiceHealth($service);
        }
        
        return $health;
    }
}

// Usage
// $dashboard = new MetricsDashboard($integrationLogger);
// 
// // Get single service health
// $mailchimpHealth = $dashboard->getServiceHealth('mailchimp', 7);
// echo "Mailchimp Health: {$mailchimpHealth['health_status']}\n";
// echo "Success Rate: {$mailchimpHealth['metrics']['success_rate']}%\n";
// 
// // Get all services
// $allHealth = $dashboard->getAllServicesHealth();
// foreach ($allHealth as $service => $data) {
//     echo "{$service}: {$data['health_status']} ({$data['metrics']['success_rate']}%)\n";
// }

echo "Advanced usage examples completed successfully!\n";
