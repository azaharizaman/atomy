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
 * Stripe Gateway Implementation.
 */
final class StripeGateway implements GatewayInterface
{
    private const API_URL = 'https://api.stripe.com/v1';
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
        return GatewayProvider::STRIPE;
    }

    public function getName(): string
    {
        return 'Stripe';
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

        $endpoint = '/payment_intents';
        
        $payload = [
            'amount' => $request->amount->getAmount(), // Amount in cents
            'currency' => strtolower($request->amount->getCurrency()),
            'capture_method' => $request->capture ? 'automatic' : 'manual',
            'confirm' => true,
            'payment_method' => $request->paymentMethodToken,
            'description' => $request->description,
            'metadata' => $request->metadata,
        ];

        if ($request->customerEmail) {
            $payload['receipt_email'] = $request->customerEmail;
        }

        if ($request->returnUrl) {
            $payload['return_url'] = $request->returnUrl;
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            $gatewayStatus = match ($status) {
                'succeeded' => GatewayStatus::COMPLETED,
                'requires_capture' => GatewayStatus::AUTHORIZED,
                'requires_action' => GatewayStatus::PENDING,
                'canceled' => GatewayStatus::CANCELLED,
                default => GatewayStatus::FAILED,
            };

            if ($gatewayStatus === GatewayStatus::FAILED) {
                throw new AuthorizationFailedException("Stripe Authorization Failed: Status {$status}");
            }

            return new AuthorizationResult(
                transactionId: $id,
                status: $gatewayStatus,
                gatewayReference: $id,
                rawResponse: $response,
                avsResult: null,
                cvvResult: null
            );

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("Stripe Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();

        $endpoint = "/payment_intents/{$request->transactionId}/capture";
        
        $payload = [];
        if ($request->amount) {
            $payload['amount_to_capture'] = $request->amount->getAmount();
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            if ($status === 'succeeded') {
                return new CaptureResult(
                    transactionId: $id,
                    status: GatewayStatus::COMPLETED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new CaptureFailedException("Stripe Capture Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new CaptureFailedException("Stripe Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();

        $endpoint = '/refunds';

        $payload = [
            'payment_intent' => $request->transactionId,
        ];
        if ($request->amount) {
            $payload['amount'] = $request->amount->getAmount();
        }
        if ($request->reason) {
            $payload['reason'] = $request->reason;
        }

        try {
            $response = $this->sendRequest('POST', $endpoint, $payload);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            if ($status === 'succeeded' || $status === 'pending') {
                return new RefundResult(
                    transactionId: $id,
                    status: $status === 'succeeded' ? GatewayStatus::REFUNDED : GatewayStatus::PENDING,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new RefundFailedException("Stripe Refund Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new RefundFailedException("Stripe Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();

        $endpoint = "/payment_intents/{$request->transactionId}/cancel";

        try {
            $response = $this->sendRequest('POST', $endpoint, []);

            $status = $response['status'] ?? null;
            $id = $response['id'] ?? null;

            if ($status === 'canceled') {
                return new VoidResult(
                    transactionId: $id,
                    status: GatewayStatus::CANCELLED,
                    gatewayReference: $id,
                    rawResponse: $response
                );
            }

            throw new VoidFailedException("Stripe Void Failed: Status {$status}");

        } catch (\Throwable $e) {
            throw new VoidFailedException("Stripe Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        throw new GatewayException("Evidence submission not implemented for Stripe yet.");
    }

    private function ensureInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new GatewayException("Stripe Gateway not initialized with credentials.");
        }
    }

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        $url = self::API_URL . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->credentials->apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->client->request($method, $url, $data, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("Stripe API Error: " . $response->getBody());
        }

        return json_decode($response->getBody(), true);
    }
}
