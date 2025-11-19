<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\PaymentGatewayConnectorInterface;
use Nexus\Connector\Exceptions\{ConnectionException, PaymentDeclinedException};
use PayPalCheckoutSdk\Core\{PayPalHttpClient, ProductionEnvironment, SandboxEnvironment};
use PayPalCheckoutSdk\Orders\{OrdersCreateRequest, OrdersCaptureRequest, OrdersGetRequest};
use PayPalCheckoutSdk\Payments\{CapturesRefundRequest};

/**
 * PayPal payment gateway adapter.
 *
 * This adapter translates the generic PaymentGatewayConnectorInterface
 * into PayPal-specific API calls using the Checkout SDK.
 */
final class PayPalPaymentAdapter implements PaymentGatewayConnectorInterface
{
    private PayPalHttpClient $client;

    /**
     * @param string $clientId PayPal client ID
     * @param string $clientSecret PayPal client secret
     * @param bool $sandbox Use sandbox environment
     */
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly bool $sandbox = false
    ) {
        $environment = $this->sandbox
            ? new SandboxEnvironment($this->clientId, $this->clientSecret)
            : new ProductionEnvironment($this->clientId, $this->clientSecret);

        $this->client = new PayPalHttpClient($environment);
    }

    /**
     * {@inheritDoc}
     */
    public function charge(float $amount, string $currency, array $paymentMethod, array $options = []): array
    {
        try {
            // Create order
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                        'description' => $options['description'] ?? 'Payment',
                    ],
                ],
            ];

            if (isset($paymentMethod['payment_source'])) {
                $request->body['payment_source'] = $paymentMethod['payment_source'];
            }

            $response = $this->client->execute($request);
            $orderId = $response->result->id;

            // Capture order immediately
            $captureRequest = new OrdersCaptureRequest($orderId);
            $captureResponse = $this->client->execute($captureRequest);

            $capture = $captureResponse->result->purchase_units[0]->payments->captures[0] ?? null;

            if ($capture === null) {
                throw new \RuntimeException('PayPal capture data not found in response');
            }

            return [
                'transaction_id' => $capture->id,
                'status' => strtolower($capture->status),
                'amount' => (float) $capture->amount->value,
            ];

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'DECLINED') || str_contains($e->getMessage(), 'REJECTED')) {
                throw PaymentDeclinedException::declined(
                    reason: $e->getMessage(),
                    code: 'DECLINED'
                );
            }

            throw ConnectionException::requestFailed(
                message: "PayPal charge failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, ?float $amount = null, array $options = []): array
    {
        try {
            $request = new CapturesRefundRequest($transactionId);
            $request->body = [];

            if ($amount !== null) {
                // Need to get original capture to get currency
                $getRequest = new OrdersGetRequest($transactionId);
                $getResponse = $this->client->execute($getRequest);
                $currency = $getResponse->result->purchase_units[0]->amount->currency_code ?? 'USD';

                $request->body = [
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => $currency,
                    ],
                ];
            }

            if (isset($options['note_to_payer'])) {
                $request->body['note_to_payer'] = $options['note_to_payer'];
            }

            $response = $this->client->execute($request);

            return [
                'refund_id' => $response->result->id,
                'status' => strtolower($response->result->status),
                'amount' => (float) $response->result->amount->value,
            ];

        } catch (\Exception $e) {
            throw ConnectionException::requestFailed(
                message: "PayPal refund failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentIntent(float $amount, string $currency, array $options = []): array
    {
        try {
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'AUTHORIZE', // For deferred capture
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                        'description' => $options['description'] ?? 'Payment Intent',
                    ],
                ],
            ];

            $response = $this->client->execute($request);

            return [
                'intent_id' => $response->result->id,
                'client_secret' => $response->result->id, // PayPal uses order ID
                'status' => strtolower($response->result->status),
            ];

        } catch (\Exception $e) {
            throw ConnectionException::requestFailed(
                message: "PayPal payment intent creation failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function captureAuthorization(string $intentId, ?float $amount = null): array
    {
        try {
            $request = new OrdersCaptureRequest($intentId);

            if ($amount !== null) {
                // PayPal requires final_capture flag if capturing partial amount
                $request->body = [
                    'final_capture' => true,
                ];
            }

            $response = $this->client->execute($request);
            $capture = $response->result->purchase_units[0]->payments->captures[0] ?? null;

            if ($capture === null) {
                throw new \RuntimeException('PayPal capture data not found in response');
            }

            return [
                'transaction_id' => $capture->id,
                'status' => strtolower($capture->status),
                'amount' => (float) $capture->amount->value,
            ];

        } catch (\Exception $e) {
            throw ConnectionException::requestFailed(
                message: "PayPal payment capture failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }
}
