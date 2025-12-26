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
 * Braintree Gateway Implementation.
 * Note: Braintree typically uses a dedicated SDK, but we are using the HttpClientInterface
 * to maintain consistency and avoid extra dependencies if possible, or simulating the API calls.
 * For this implementation, we will assume a direct GraphQL or REST API usage which Braintree supports.
 */
final class BraintreeGateway implements GatewayInterface
{
    private const API_URL_SANDBOX = 'https://payments.sandbox.braintree-api.com/graphql';
    private const API_URL_LIVE = 'https://payments.braintree-api.com/graphql';
    
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
        return GatewayProvider::BRAINTREE;
    }

    public function getName(): string
    {
        return 'Braintree';
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

        // Braintree GraphQL Mutation for Authorization
        $query = <<<'GRAPHQL'
        mutation AuthorizePaymentMethod($input: AuthorizePaymentMethodInput!) {
            authorizePaymentMethod(input: $input) {
                transaction {
                    id
                    status
                    amount {
                        value
                        currencyCode
                    }
                }
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'paymentMethodId' => $request->paymentMethodToken, // Nonce
                'transaction' => [
                    'amount' => [
                        'value' => $request->amount->getAmount() / 100, // Braintree uses decimal
                        'currencyCode' => strtoupper($request->amount->getCurrency()),
                    ]
                ]
            ]
        ];

        try {
            $response = $this->sendGraphQLRequest($query, $variables);
            
            $transaction = $response['data']['authorizePaymentMethod']['transaction'] ?? null;

            if (!$transaction) {
                throw new AuthorizationFailedException("Braintree Authorization Failed: No transaction returned.");
            }

            $status = $transaction['status'];
            $id = $transaction['id'];

            // Map Braintree statuses
            $gatewayStatus = match ($status) {
                'AUTHORIZED' => GatewayStatus::AUTHORIZED,
                'SUBMITTED_FOR_SETTLEMENT' => GatewayStatus::PENDING,
                'SETTLED' => GatewayStatus::COMPLETED,
                'VOIDED' => GatewayStatus::CANCELLED,
                'FAILED', 'GATEWAY_REJECTED', 'PROCESSOR_DECLINED' => GatewayStatus::FAILED,
                default => GatewayStatus::PENDING,
            };

            return new AuthorizationResult(
                transactionId: $id,
                status: $gatewayStatus,
                gatewayReference: $id,
                rawResponse: $response
            );

        } catch (\Throwable $e) {
            throw new AuthorizationFailedException("Braintree Authorization Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->ensureInitialized();

        $query = <<<'GRAPHQL'
        mutation CaptureTransaction($input: CaptureTransactionInput!) {
            captureTransaction(input: $input) {
                transaction {
                    id
                    status
                }
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'transactionId' => $request->transactionId,
                'amount' => [
                    'value' => $request->amount->getAmount() / 100,
                    'currencyCode' => strtoupper($request->amount->getCurrency()),
                ]
            ]
        ];

        try {
            $response = $this->sendGraphQLRequest($query, $variables);
            $transaction = $response['data']['captureTransaction']['transaction'] ?? null;

            if ($transaction && ($transaction['status'] === 'SUBMITTED_FOR_SETTLEMENT' || $transaction['status'] === 'SETTLED')) {
                return new CaptureResult(
                    transactionId: $transaction['id'],
                    status: GatewayStatus::COMPLETED, // Treated as completed for capture
                    gatewayReference: $transaction['id'],
                    rawResponse: $response
                );
            }

            throw new CaptureFailedException("Braintree Capture Failed: Status " . ($transaction['status'] ?? 'Unknown'));

        } catch (\Throwable $e) {
            throw new CaptureFailedException("Braintree Capture Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->ensureInitialized();

        $query = <<<'GRAPHQL'
        mutation RefundTransaction($input: RefundTransactionInput!) {
            refundTransaction(input: $input) {
                refund {
                    id
                    status
                }
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'transactionId' => $request->transactionId,
                'amount' => [
                    'value' => $request->amount->getAmount() / 100,
                    'currencyCode' => strtoupper($request->amount->getCurrency()),
                ]
            ]
        ];

        try {
            $response = $this->sendGraphQLRequest($query, $variables);
            $refund = $response['data']['refundTransaction']['refund'] ?? null;

            if ($refund && ($refund['status'] === 'SUBMITTED_FOR_SETTLEMENT' || $refund['status'] === 'SETTLED')) {
                return new RefundResult(
                    transactionId: $refund['id'],
                    status: GatewayStatus::REFUNDED,
                    gatewayReference: $refund['id'],
                    rawResponse: $response
                );
            }

            throw new RefundFailedException("Braintree Refund Failed: Status " . ($refund['status'] ?? 'Unknown'));

        } catch (\Throwable $e) {
            throw new RefundFailedException("Braintree Refund Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->ensureInitialized();

        $query = <<<'GRAPHQL'
        mutation VoidTransaction($input: VoidTransactionInput!) {
            voidTransaction(input: $input) {
                transaction {
                    id
                    status
                }
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'transactionId' => $request->transactionId
            ]
        ];

        try {
            $response = $this->sendGraphQLRequest($query, $variables);
            $transaction = $response['data']['voidTransaction']['transaction'] ?? null;

            if ($transaction && $transaction['status'] === 'VOIDED') {
                return new VoidResult(
                    transactionId: $transaction['id'],
                    status: GatewayStatus::CANCELLED,
                    gatewayReference: $transaction['id'],
                    rawResponse: $response
                );
            }

            throw new VoidFailedException("Braintree Void Failed: Status " . ($transaction['status'] ?? 'Unknown'));

        } catch (\Throwable $e) {
            throw new VoidFailedException("Braintree Void Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        throw new GatewayException("Evidence submission not implemented for Braintree yet.");
    }

    public function getStatus(): GatewayStatus
    {
        // Check if gateway is initialized and available
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

    private function ensureInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new GatewayException("Braintree Gateway not initialized with credentials.");
        }
    }

    private function sendGraphQLRequest(string $query, array $variables): array
    {
        // Use sandbox if credentials indicate sandbox mode, otherwise use live
        $url = $this->credentials?->sandboxMode ? self::API_URL_SANDBOX : self::API_URL_LIVE;

        // Braintree Auth is typically Basic Auth with Public Key and Private Key
        // Or Access Token. We'll assume API Key / Token in credentials.
        $headers = [
            'Authorization' => 'Bearer ' . $this->credentials->apiKey,
            'Content-Type' => 'application/json',
            'Braintree-Version' => '2019-01-01',
        ];

        $payload = [
            'query' => $query,
            'variables' => $variables
        ];

        $response = $this->client->request('POST', $url, $payload, $headers);

        if ($response->getStatusCode() >= 400) {
            throw new GatewayException("Braintree API Error: " . $response->getBody());
        }

        $body = json_decode($response->getBody(), true);

        if (isset($body['errors'])) {
            $errorMsg = $body['errors'][0]['message'] ?? 'Unknown GraphQL Error';
            throw new GatewayException("Braintree GraphQL Error: " . $errorMsg);
        }

        return $body;
    }
}
