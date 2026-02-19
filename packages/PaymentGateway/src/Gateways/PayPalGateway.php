<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Gateways;

use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\DTOs\EvidenceSubmissionRequest;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\ValueObjects\EvidenceSubmissionResult;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;

/**
 * PayPal Gateway Implementation.
 *
 * NOTE: This class stores OAuth access tokens as mutable properties for session reuse.
 * PayPal requires OAuth 2.0 authentication with client credentials grant, which returns
 * access tokens valid for 8-9 hours. Storing the token avoids unnecessary auth requests
 * for each operation.
 *
 * LIFECYCLE CONSIDERATIONS:
 * - Gateway instances should be created per-request in most frameworks (not cached)
 * - If caching gateway instances, ensure token expiry is checked before each request
 * - In production, consider using an external token manager service for multi-instance deployments
 * - For single-tenant, single-instance applications, this implementation is sufficient
 */
final class PayPalGateway implements GatewayInterface
{
    private const API_URL_SANDBOX = 'https://api-m.sandbox.paypal.com';
    private const API_URL_LIVE = 'https://api-m.paypal.com';
    
    private ?GatewayCredentials $credentials = null;
    
    /**
     * OAuth access token cached for the lifetime of this instance.
     * @var string|null
     */
    private ?string $accessToken = null;
    
    /**
     * Unix timestamp when the current access token expires.
     * @var int|null
     */
    private ?int $tokenExpiry = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        ?GatewayCredentials $credentials = null
    ) {
        if ($credentials) {
            $this->initialize($credentials);
        }
    }

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::PAYPAL;
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function initialize(GatewayCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function isInitialized(): bool
    {
        return $this->credentials !== null;
    }

    public function authorize(AuthorizeRequest $request): AuthorizationResult
    {
        $this->ensureInitialized();
        $this->authenticate();

        $endpoint = '/v2/checkout/orders';
        
        $payload = [
            'intent' => $request->capture ? 'CAPTURE' : 'AUTHORIZE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($request->amount->getCurrency()),
                    'value' => number_format($request->amount->getAmount() / 100, 2, '.', ''),
                ],
                'description' => $request->description,
            ]],
        ];

        if ($request->returnUrl) {
            $payload['application_context'] = [
                'return_url' => $request->returnUrl,
                'cancel_url' => $request->returnUrl, // Simplified for now
            ];
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            // PayPal statuses: CREATED, SAVED, APPROVED, VOIDED, COMPLETED, PAYER_ACTION_REQUIRED
            $gatewayStatus = match ($status) {
                'COMPLETED' => GatewayStatus::COMPLETED,
                'APPROVED' => GatewayStatus::AUTHORIZED,
                'CREATED', 'PAYER_ACTION_REQUIRED' => GatewayStatus::PENDING,
                'VOIDED' => GatewayStatus::CANCELLED,
                default => GatewayStatus::FAILED,
            };

            return new AuthorizationResult(
                transactionId: $id,
                status: $gatewayStatus,
                gatewayReference: $id,
                rawResponse: $response,
                avsResult: null,
                cvvResult: null
            );

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("PayPal Authorization Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();
        $this->authenticate();

        // For PayPal, we capture an authorized order
        $endpoint = "/v2/checkout/orders/{$request->transactionId}/capture";
        
        $payload = []; // PayPal capture usually captures the full authorized amount unless specified

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            if ($status === 'COMPLETED') {
                return new CaptureResult(
                    transactionId: $id,
                    status: GatewayStatus::COMPLETED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new CaptureFailedException("PayPal Capture Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new CaptureFailedException("PayPal Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();
        $this->authenticate();

        // PayPal refund endpoint is usually on the capture ID, not the order ID
        // Assuming transactionId passed here is the capture ID
        $endpoint = "/v2/payments/captures/{$request->transactionId}/refund";

        $payload = [];
        if ($request->amount) {
            $payload['amount'] = [
                'currency_code' => strtoupper($request->amount->getCurrency()),
                'value' => number_format($request->amount->getAmount() / 100, 2, '.', ''),
            ];
        }
        if ($request->reason) {
            $payload['note_to_payer'] = $request->reason;
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            if ($status === 'COMPLETED') {
                return new RefundResult(
                    transactionId: $id,
                    status: GatewayStatus::REFUNDED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new RefundFailedException("PayPal Refund Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new RefundFailedException("PayPal Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();
        $this->authenticate();

        // Void an authorized payment (Authorization)
        // NOTE: PayPal v2 API only supports voiding authorizations, not orders.
        // - For CREATED/APPROVED orders that haven't been authorized, they will expire automatically
        // - This method assumes $request->transactionId is an Authorization ID from the 
        //   /v2/payments/authorizations endpoint
        // - For order IDs, there is no direct void endpoint; instead, do not capture the authorization
        
        // Assuming transactionId is an Authorization ID
        $endpoint = "/v2/payments/authorizations/{$request->transactionId}/void";

        try {
            $response = $this->sendRequest('POST', $endpoint, []);

            // 204 No Content on success usually
            return new VoidResult(
                transactionId: $request->transactionId,
                status: GatewayStatus::CANCELLED,
                gatewayReference: $request->transactionId,
                rawResponse: $response
            );

        } catch (\Throwable $e) {
            throw new VoidFailedException("PayPal Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        $this->ensureInitialized();

        try {
            // PayPal dispute evidence submission
            // See: https://developer.paypal.com/docs/api/reports/v1/#disputes-evidence
            
            $endpoint = '/v1/customer/disputes/' . $request->disputeId . '/provide-evidence';
            
            $payload = [];
            
            // Add evidence based on type
            if ($request->textEvidence) {
                $payload['evidence'] = [
                    [
                        'evidence_type' => 'PROOF_OF_FULFILLMENT_DELIVERY',
                        'evidence_info' => [
                            'notes' => $request->textEvidence,
                        ],
                    ],
                ];
            }
            
            // Add file evidence if provided
            if (!empty($request->fileIds)) {
                $payload['evidence'] = $payload['evidence'] ?? [];
                foreach ($request->fileIds as $fileId) {
                    $payload['evidence'][] = [
                        'evidence_type' => 'FILE_ATTACHMENT',
                        'evidence_info' => [
                            'file_id' => $fileId,
                        ],
                    ];
                }
            }
            
            $response = $this->sendRequest('POST', $endpoint, $payload);
            
            return EvidenceSubmissionResult::success(
                submissionId: $response['dispute_id'] ?? $request->disputeId,
                status: $response['status'] ?? 'pending'
            );
            
        } catch (\Throwable $e) {
            return EvidenceSubmissionResult::failure(
                'PayPal evidence submission failed: ' . $e->getMessage()
            );
        }
    }

    public function getStatus(): GatewayStatus
    {
        // Check if gateway is initialized and available
        return $this->isInitialized() ? GatewayStatus::ACTIVE : GatewayStatus::INACTIVE;
    }

    public function supports3ds(): bool
    {
        return false;
    }

    public function supportsTokenization(): bool
    {
        return false;
    }

    public function supportsPartialCapture(): bool
    {
        return true;
    }

    public function supportsPartialRefund(): bool
    {
        return true;
    }

    private function ensureInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new GatewayException("PayPal Gateway not initialized with credentials.");
        }
    }

    private function authenticate(): void
    {
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return;
        }

        $endpoint = '/v1/oauth2/token';
        $url = $this->getBaseUrl() . $endpoint;
        
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->credentials->apiKey . ':' . $this->credentials->apiSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->client->request('POST', $url, ['grant_type' => 'client_credentials'], $headers);
        
        $data = json_decode($response->getBody(), true);
        
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + $data['expires_in'];
    }

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        $url = $this->getBaseUrl() . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request($method, $url, $data, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("PayPal API Error: " . $response->getBody());
        }

        if ($response->getStatusCode() === 204) {
            return [];
        }

        return json_decode($response->getBody(), true);
    }

    private function getBaseUrl(): string
    {
        // Use sandbox if credentials indicate sandbox mode, otherwise use live
        if ($this->credentials?->sandboxMode) {
            return self::API_URL_SANDBOX;
        }
        
        return self::API_URL_LIVE;
    }
}
