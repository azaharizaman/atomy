<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Gateways;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\TransactionResult;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\TransactionFailedException;

/**
 * Adyen Gateway Implementation.
 *
 * Handles payments via Adyen Checkout API v70.
 */
final class AdyenGateway implements GatewayInterface
{
    private const API_VERSION = 'v70';
    private const TEST_URL = 'https://checkout-test.adyen.com/' . self::API_VERSION;
    private const LIVE_URL = 'https://checkout-live.adyen.com/' . self::API_VERSION; // Note: Live URL usually has a prefix

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly GatewayCredentials $credentials
    ) {}

    public function authorize(array $payload): TransactionResult
    {
        // Adyen /payments endpoint
        // Requires 'merchantAccount' and 'amount'
        
        $requestPayload = array_merge([
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => $payload['reference'] ?? uniqid('adyen_'),
            // Default to manual capture if not specified, though Adyen usually defaults to auto
            // 'captureDelayHours' => -1 // Manual capture
        ], $payload);

        // Ensure amount is formatted correctly for Adyen (currency, value)
        // Assuming payload['amount'] is passed as [currency => 'USD', value => 1000]
        // If the caller passes flat amount, we might need transformation, but let's assume payload is prepared.

        try {
            $response = $this->sendRequest('POST', '/payments', $requestPayload);

            $resultCode = $response['resultCode'] ?? 'Error';
            $pspReference = $response['pspReference'] ?? null;

            if ($resultCode === 'Authorised') {
                return new TransactionResult(
                    transactionId: $pspReference,
                    status: TransactionStatus::AUTHORIZED,
                    rawResponse: $response,
                    gatewayReference: $pspReference
                );
            }

            if ($resultCode === 'Refused') {
                return new TransactionResult(
                    transactionId: $pspReference,
                    status: TransactionStatus::DECLINED,
                    rawResponse: $response,
                    gatewayReference: $pspReference,
                    errorMessage: $response['refusalReason'] ?? 'Payment refused'
                );
            }

            throw new TransactionFailedException("Adyen Authorization Failed: {$resultCode}");

        } catch (\Throwable $e) {
            throw new GatewayException("Adyen Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(string $transactionId, array $payload = []): TransactionResult
    {
        // Adyen /payments/{id}/captures
        $endpoint = "/payments/{$transactionId}/captures";
        
        $requestPayload = array_merge([
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => $payload['reference'] ?? uniqid('cap_'),
        ], $payload);

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                // Adyen captures are asynchronous. 'received' means it's processing.
                return new TransactionResult(
                    transactionId: $pspReference, // This is the capture reference
                    status: TransactionStatus::PENDING, // Or COMPLETED depending on how we treat async
                    rawResponse: $response,
                    gatewayReference: $pspReference
                );
            }

            throw new TransactionFailedException("Adyen Capture Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new GatewayException("Adyen Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(string $transactionId, array $payload = []): TransactionResult
    {
        // Adyen /payments/{id}/refunds
        $endpoint = "/payments/{$transactionId}/refunds";

        $requestPayload = array_merge([
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => $payload['reference'] ?? uniqid('ref_'),
        ], $payload);

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                return new TransactionResult(
                    transactionId: $pspReference,
                    status: TransactionStatus::PENDING,
                    rawResponse: $response,
                    gatewayReference: $pspReference
                );
            }

            throw new TransactionFailedException("Adyen Refund Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new GatewayException("Adyen Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(string $transactionId, array $payload = []): TransactionResult
    {
        // Adyen /payments/{id}/cancels
        // Used to cancel an authorization before it is captured
        $endpoint = "/payments/{$transactionId}/cancels";

        $requestPayload = array_merge([
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => $payload['reference'] ?? uniqid('void_'),
        ], $payload);

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                return new TransactionResult(
                    transactionId: $pspReference,
                    status: TransactionStatus::CANCELLED,
                    rawResponse: $response,
                    gatewayReference: $pspReference
                );
            }

            throw new TransactionFailedException("Adyen Void Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new GatewayException("Adyen Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(string $disputeId, array $evidence): void
    {
        // Adyen Dispute API is separate, but for now we can stub or implement if needed.
        // Adyen uses /disputes endpoint usually.
        // For this iteration, we'll throw not implemented or basic stub.
        throw new GatewayException("Adyen dispute evidence submission not yet implemented.");
    }

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        $baseUrl = $this->credentials->sandboxMode ? self::TEST_URL : self::LIVE_URL;
        // Note: Live URL prefix handling is complex in Adyen (e.g. [random]-[company]-checkout-live...), 
        // usually configured in credentials. For now, we use a placeholder or assume config has full URL.
        
        if (!$this->credentials->sandboxMode && isset($this->credentials->additionalConfig['live_url_prefix'])) {
            $baseUrl = "https://{$this->credentials->additionalConfig['live_url_prefix']}-checkout-live.adyenpayments.com/checkout/" . self::API_VERSION;
        }

        $url = $baseUrl . $endpoint;

        $headers = [
            'X-API-Key' => $this->credentials->apiKey,
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request($method, $url, $data, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("Adyen API Error: " . $response->getBody());
        }

        return json_decode($response->getBody(), true);
    }
}
