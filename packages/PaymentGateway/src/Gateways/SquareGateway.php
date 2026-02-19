<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Gateways;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\EvidenceSubmissionRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\EvidenceSubmissionResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;

/**
 * Square Gateway Implementation.
 */
final class SquareGateway implements GatewayInterface
{
    private const API_URL_SANDBOX = 'https://connect.squareupsandbox.com';
    private const API_URL_LIVE = 'https://connect.squareup.com';
    
    private ?GatewayCredentials $credentials = null;

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
        return GatewayProvider::SQUARE;
    }

    public function getName(): string
    {
        return 'Square';
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

        $endpoint = '/v2/payments';
        
        // Use request's idempotency key or generate one if not provided
        $idempotencyKey = $request->idempotencyKey ?? uniqid('sq_', true);

        $payload = [
            'source_id' => $request->paymentMethodToken, // Nonce from Square frontend
            'idempotency_key' => $idempotencyKey,
            'amount_money' => [
                'amount' => $request->amount->getAmount(), // Square uses lowest denomination (cents)
                'currency' => strtoupper($request->amount->getCurrency()),
            ],
            'autocomplete' => $request->capture, // If true, captures immediately
        ];

        if ($request->description) {
            $payload['note'] = $request->description;
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);
            $payment = $response['payment'] ?? [];

            $status = $payment['status'] ?? null;
            $id = $payment['id'] ?? null;

            // Square statuses: COMPLETED, CANCELED, FAILED, APPROVED
            $gatewayStatus = match ($status) {
                'COMPLETED' => GatewayStatus::COMPLETED,
                'APPROVED' => GatewayStatus::AUTHORIZED,
                'CANCELED' => GatewayStatus::CANCELLED,
                'FAILED' => GatewayStatus::FAILED,
                default => GatewayStatus::PENDING,
            };

            return new AuthorizationResult(
                transactionId: $id,
                status: $gatewayStatus,
                gatewayReference: $id,
                rawResponse: $response,
                avsResult: $payment['card_details']['avs_status'] ?? null,
                cvvResult: $payment['card_details']['cvv_status'] ?? null
            );

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("Square Authorization Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();

        $endpoint = "/v2/payments/{$request->authorizationId}/complete";
        
        $payload = []; 

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);
            $payment = $response['payment'] ?? [];

            $status = $payment['status'] ?? null;
            $id = $payment['id'] ?? null;

            if ($status === 'COMPLETED') {
                return new CaptureResult(
                    transactionId: $id,
                    status: GatewayStatus::COMPLETED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new CaptureFailedException("Square Capture Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new CaptureFailedException("Square Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();

        $endpoint = '/v2/refunds';
        // Use request's idempotency key or generate one if not provided
        $idempotencyKey = $request->idempotencyKey ?? uniqid('sq_ref_', true);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'payment_id' => $request->transactionId,
            'amount_money' => [
                'amount' => $request->amount->getAmount(),
                'currency' => strtoupper($request->amount->getCurrency()),
            ],
        ];

        if ($request->reason) {
            $payload['reason'] = $request->reason;
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);
            $refund = $response['refund'] ?? [];

            $status = $refund['status'] ?? null;
            $id = $refund['id'] ?? null;

            if ($status === 'COMPLETED' || $status === 'PENDING') {
                return new RefundResult(
                    transactionId: $id,
                    status: GatewayStatus::REFUNDED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new RefundFailedException("Square Refund Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new RefundFailedException("Square Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();

        $endpoint = "/v2/payments/{$request->transactionId}/cancel";

        try {
            $response = $this->sendRequest('POST', $endpoint, []);
            $payment = $response['payment'] ?? [];
            
            $status = $payment['status'] ?? null;

            if ($status === 'CANCELED') {
                return new VoidResult(
                    transactionId: $request->transactionId,
                    status: GatewayStatus::CANCELLED,
                    gatewayReference: $request->transactionId,
                    rawResponse: $response
                );
            }
            
            throw new VoidFailedException("Square Void Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new VoidFailedException("Square Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        $this->ensureInitialized();

        try {
            // Square dispute evidence submission
            // See: https://developer.squareup.com/docs/disputes-api/manage-disputes#submit-evidence
            
            $endpoint = '/v2/disputes/' . $request->disputeId . '/evidence';
            
            $payload = [];
            
            // Add text evidence if provided
            if ($request->textEvidence) {
                $payload['evidence'] = [
                    'type' => 'FILE',
                    'evidence_type' => 'REBUTTAL',
                    'content' => base64_encode($request->textEvidence),
                ];
            }
            
            // Add file evidence if provided
            if (!empty($request->fileIds)) {
                $payload['evidence'] = [
                    'type' => 'FILE',
                    'evidence_type' => 'PROOF_OF_FULFILLMENT',
                    'file_id' => $request->fileIds[0],
                ];
            }
            
            $response = $this->sendRequest('POST', $endpoint, $payload);
            
            return EvidenceSubmissionResult::success(
                submissionId: $response['evidence']['id'] ?? $request->disputeId,
                status: 'submitted'
            );
            
        } catch (\Throwable $e) {
            return EvidenceSubmissionResult::failure(
                'Square evidence submission failed: ' . $e->getMessage()
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
        return true;
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
            throw new GatewayException("Square Gateway not initialized with credentials.");
        }
    }

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        $url = $this->getBaseUrl() . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->credentials->apiKey,
            'Content-Type' => 'application/json',
            'Square-Version' => '2023-10-20',
        ];

        $response = $this->client->request($method, $url, $data, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("Square API Error: " . $response->getBody());
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
