<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;

/**
 * Contract for payment gateway implementations.
 *
 * Each gateway (Stripe, PayPal, Square, Adyen) must implement this interface.
 * The gateway is responsible for translating between Nexus DTOs and
 * the specific gateway's API format.
 */
interface GatewayInterface
{
    /**
     * Get the gateway provider type.
     */
    public function getProvider(): GatewayProvider;

    /**
     * Get the gateway display name.
     */
    public function getName(): string;

    /**
     * Initialize the gateway with credentials.
     */
    public function initialize(GatewayCredentials $credentials): void;

    /**
     * Check if the gateway is initialized.
     */
    public function isInitialized(): bool;

    /**
     * Authorize a payment (hold funds without capturing).
     *
     * @throws \Nexus\PaymentGateway\Exceptions\AuthorizationFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayException
     */
    public function authorize(AuthorizeRequest $request): AuthorizationResult;

    /**
     * Capture a previously authorized payment.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\CaptureFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayException
     */
    public function capture(CaptureRequest $request): CaptureResult;

    /**
     * Refund a captured payment.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\RefundFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayException
     */
    public function refund(RefundRequest $request): RefundResult;

    /**
     * Void/cancel an authorization.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\VoidFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayException
     */
    public function void(VoidRequest $request): VoidResult;

    /**
     * Check gateway health/availability.
     */
    public function getStatus(): GatewayStatus;

    /**
     * Check if gateway supports 3D Secure.
     */
    public function supports3ds(): bool;

    /**
     * Check if gateway supports tokenization.
     */
    public function supportsTokenization(): bool;

    /**
     * Check if gateway supports partial captures.
     */
    public function supportsPartialCapture(): bool;

    /**
     * Check if gateway supports partial refunds.
     */
    public function supportsPartialRefund(): bool;
}
