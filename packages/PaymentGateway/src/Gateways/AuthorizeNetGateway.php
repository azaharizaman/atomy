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
 * Authorize.Net Gateway Implementation.
 * Uses JSON API.
 */
final class AuthorizeNetGateway implements GatewayInterface
{
    private const API_URL_SANDBOX = 'https://apitest.authorize.net/xml/v1/request.api';
    private const API_URL_LIVE = 'https://api.authorize.net/xml/v1/request.api';
    
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
        return GatewayProvider::AUTHORIZE_NET;
    }

    public function getName(): string
    {
        return 'Authorize.Net';
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

        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => $this->getAuthBlock(),
                'transactionRequest' => [
                    'transactionType' => $request->capture ? 'authCaptureTransaction' : 'authOnlyTransaction',
                    'amount' => $request->amount->getAmount() / 100, // Decimal
                    'payment' => [
                        'opaqueData' => [
                            'dataDescriptor' => 'COMMON.ACCEPT.INAPP.PAYMENT',
                            'dataValue' => $request->paymentMethodToken, // Nonce
                        ]
                    ],
                    'order' => [
                        'description' => $request->description
                    ]
                ]
            ]
        ];

        try {
            $response = $this->sendRequest($payload);
            $transactionResponse = $response['transactionResponse'] ?? [];

            $responseCode = $transactionResponse['responseCode'] ?? '0';
            $transId = $transactionResponse['transId'] ?? null;

            // Response Code: 1 = Approved, 2 = Declined, 3 = Error, 4 = Held for Review
            $gatewayStatus = match ($responseCode) {
                '1' => $request->capture ? GatewayStatus::COMPLETED : GatewayStatus::AUTHORIZED,
                '2' => GatewayStatus::FAILED,
                '3' => GatewayStatus::FAILED,
                '4' => GatewayStatus::PENDING,
                default => GatewayStatus::FAILED,
            };

            if ($gatewayStatus === GatewayStatus::FAILED) {
                $errorText = $transactionResponse['errors'][0]['errorText'] ?? 'Unknown Error';
                throw new AuthorizationFailedException("Authorize.Net Authorization Failed: {$errorText}");
            }

            return new AuthorizationResult(
                transactionId: $transId,
                status: $gatewayStatus,
                gatewayReference: $transId,
                rawResponse: $response,
                avsResult: $transactionResponse['avsResultCode'] ?? null,
                cvvResult: $transactionResponse['cvvResultCode'] ?? null
            );

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("Authorize.Net Authorization Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();

        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => $this->getAuthBlock(),
                'transactionRequest' => [
                    'transactionType' => 'priorAuthCaptureTransaction',
                    'amount' => $request->amount->getAmount() / 100,
                    'refTransId' => $request->transactionId
                ]
            ]
        ];

        try {
            $response = $this->sendRequest($payload);
            $transactionResponse = $response['transactionResponse'] ?? [];
            $responseCode = $transactionResponse['responseCode'] ?? '0';

            if ($responseCode === '1') {
                return new CaptureResult(
                    transactionId: $transactionResponse['transId'],
                    status: GatewayStatus::COMPLETED,
                    gatewayReference: $transactionResponse['transId'],
                    rawResponse: $response
                );
            }

            $errorText = $transactionResponse['errors'][0]['errorText'] ?? 'Unknown Error';
            throw new CaptureFailedException("Authorize.Net Capture Failed: {$errorText}");

        } catch (\Throwable $e) {
            throw new CaptureFailedException("Authorize.Net Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();

        // Note: Refund requires last 4 digits of card or full card number in some cases depending on config.
        // Assuming we have a token or linked transaction capability.
        // For Authorize.Net, refundTransaction usually requires card number or payment profile.
        // If we don't have it, we might need to void if unsettled, or use a different method.
        // Here we assume we can reference the transaction.

        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => $this->getAuthBlock(),
                'transactionRequest' => [
                    'transactionType' => 'refundTransaction',
                    'amount' => $request->amount->getAmount() / 100,
                    'refTransId' => $request->transactionId,
                    'payment' => [
                        'creditCard' => [
                            'cardNumber' => 'XXXX', // Placeholder, often required
                            'expirationDate' => 'XX/XX'
                        ]
                    ]
                ]
            ]
        ];

        // NOTE: Real implementation needs card details or Customer Profile ID for refunds in Auth.Net
        // This is a simplified placeholder.

        try {
            $response = $this->sendRequest($payload);
            $transactionResponse = $response['transactionResponse'] ?? [];
            $responseCode = $transactionResponse['responseCode'] ?? '0';

            if ($responseCode === '1') {
                return new RefundResult(
                    transactionId: $transactionResponse['transId'],
                    status: GatewayStatus::REFUNDED,
                    gatewayReference: $transactionResponse['transId'],
                    rawResponse: $response
                );
            }

            $errorText = $transactionResponse['errors'][0]['errorText'] ?? 'Unknown Error';
            throw new RefundFailedException("Authorize.Net Refund Failed: {$errorText}");

        } catch (\Throwable $e) {
            throw new RefundFailedException("Authorize.Net Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();

        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => $this->getAuthBlock(),
                'transactionRequest' => [
                    'transactionType' => 'voidTransaction',
                    'refTransId' => $request->transactionId
                ]
            ]
        ];

        try {
            $response = $this->sendRequest($payload);
            $transactionResponse = $response['transactionResponse'] ?? [];
            $responseCode = $transactionResponse['responseCode'] ?? '0';

            if ($responseCode === '1') {
                return new VoidResult(
                    transactionId: $transactionResponse['transId'],
                    status: GatewayStatus::CANCELLED,
                    gatewayReference: $transactionResponse['transId'],
                    rawResponse: $response
                );
            }

            $errorText = $transactionResponse['errors'][0]['errorText'] ?? 'Unknown Error';
            throw new VoidFailedException("Authorize.Net Void Failed: {$errorText}");

        } catch (\Throwable $e) {
            throw new VoidFailedException("Authorize.Net Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        throw new GatewayException("Evidence submission not implemented for Authorize.Net yet.");
    }

    public function getStatus(string $transactionId = ''): GatewayStatus
    {
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
            throw new GatewayException("Authorize.Net Gateway not initialized with credentials.");
        }
    }

    private function getAuthBlock(): array
    {
        return [
            'name' => $this->credentials->apiKey, // API Login ID
            'transactionKey' => $this->credentials->apiSecret, // Transaction Key
        ];
    }

    private function sendRequest(array $payload): array
    {
        $url = self::API_URL_SANDBOX;

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request('POST', $url, $payload, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("Authorize.Net API Error: " . $response->getBody());
        }

        // Remove BOM if present
        $body = trim($response->getBody(), "\xEF\xBB\xBF");
        
        return json_decode($body, true);
    }
}
