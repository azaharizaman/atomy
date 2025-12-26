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
 * Adyen Gateway Implementation.
 *
 * Handles payments via Adyen Checkout API v70.
 */
final class AdyenGateway implements GatewayInterface
{
    private const API_VERSION = 'v70';
    private const TEST_URL = 'https://checkout-test.adyen.com/' . self::API_VERSION;
    
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
        return GatewayProvider::ADYEN;
    }

    public function getName(): string
    {
        return 'Adyen';
    }

    public function initialize(GatewayCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function isInitialized(): bool
    {
        return $this->credentials !== null;
    }

    private function ensureInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new GatewayException("Adyen Gateway not initialized with credentials.");
        }
    }

    public function authorize(AuthorizeRequest $request): AuthorizationResult
    {
        $this->ensureInitialized();

        $requestPayload = [
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => $request->reference ?? uniqid('adyen_'),
            'amount' => [
                'currency' => strtoupper($request->amount->getCurrency()),
                'value' => $request->amount->getAmount(),
            ],
            'paymentMethod' => [
                'type' => 'scheme',
                'encryptedCardNumber' => $request->paymentMethodToken, // Assuming token is passed here
            ],
            'returnUrl' => $request->returnUrl ?? 'https://example.com/return',
        ];

        if (!$request->capture) {
            $requestPayload['captureDelayHours'] = -1; // Manual capture
        }

        try {
            $response = $this->sendRequest('POST', '/payments', $requestPayload);

            $resultCode = $response['resultCode'] ?? 'Error';
            $pspReference = $response['pspReference'] ?? null;

            if ($resultCode === 'Authorised') {
                return new AuthorizationResult(
                    transactionId: $pspReference,
                    status: GatewayStatus::AUTHORIZED,
                    gatewayReference: $pspReference,
                    rawResponse: $response
                );
            }

            if ($resultCode === 'Refused') {
                return new AuthorizationResult(
                    transactionId: $pspReference,
                    status: GatewayStatus::DECLINED,
                    gatewayReference: $pspReference,
                    rawResponse: $response,
                    errorMessage: $response['refusalReason'] ?? 'Payment refused'
                );
            }

            throw new AuthorizationFailedException("Adyen Authorization Failed: {$resultCode}");

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("Adyen Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();
        $endpoint = "/payments/{$request->transactionId}/captures";
        
        $requestPayload = [
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => uniqid('cap_'),
            'amount' => [
                'currency' => strtoupper($request->amount->getCurrency()),
                'value' => $request->amount->getAmount(),
            ],
        ];

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                return new CaptureResult(
                    transactionId: $pspReference,
                    status: GatewayStatus::PENDING,
                    gatewayReference: $pspReference,
                    rawResponse: $response
                );
            }

            throw new CaptureFailedException("Adyen Capture Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new CaptureFailedException("Adyen Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();
        $endpoint = "/payments/{$request->transactionId}/refunds";

        $requestPayload = [
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => uniqid('ref_'),
            'amount' => [
                'currency' => strtoupper($request->amount->getCurrency()),
                'value' => $request->amount->getAmount(),
            ],
        ];

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                return new RefundResult(
                    transactionId: $pspReference,
                    status: GatewayStatus::PENDING,
                    gatewayReference: $pspReference,
                    rawResponse: $response
                );
            }

            throw new RefundFailedException("Adyen Refund Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new RefundFailedException("Adyen Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();
        $endpoint = "/payments/{$request->transactionId}/cancels";

        $requestPayload = [
            'merchantAccount' => $this->credentials->merchantId,
            'reference' => uniqid('void_'),
        ];

        try {
            $response = $this->sendRequest('POST', $endpoint, $requestPayload);

            $status = $response['status'] ?? null;
            $pspReference = $response['pspReference'] ?? null;

            if ($status === 'received') {
                return new VoidResult(
                    transactionId: $pspReference,
                    status: GatewayStatus::CANCELLED,
                    gatewayReference: $pspReference,
                    rawResponse: $response
                );
            }

            throw new VoidFailedException("Adyen Void Failed: " . json_encode($response));

        } catch (\Throwable $e) {
            throw new VoidFailedException("Adyen Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        throw new GatewayException("Adyen dispute evidence submission not yet implemented.");
    }

    public function getStatus(string $transactionId = ''): GatewayStatus
    {
        // Adyen doesn't have a simple "check gateway status" endpoint that returns a single status enum.
        // But we can check if we can reach the API.
        // Or if transactionId is provided, check that transaction.
        
        // For now, return ACTIVE if initialized.
        return $this->isInitialized() ? GatewayStatus::ACTIVE : GatewayStatus::INACTIVE;
    }

    public function supports3ds(): bool
    {
        return true;
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

    private function sendRequest(string $method, string $endpoint, array $data): array
    {
        $baseUrl = $this->credentials->sandboxMode ? self::TEST_URL : $this->getLiveUrl();
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

    private function getLiveUrl(): string
    {
        if (isset($this->credentials->additionalConfig['live_url_prefix'])) {
            return "https://{$this->credentials->additionalConfig['live_url_prefix']}-checkout-live.adyenpayments.com/checkout/" . self::API_VERSION;
        }
        // Fallback or throw
        throw new GatewayException("Adyen Live URL prefix not configured.");
    }
}
