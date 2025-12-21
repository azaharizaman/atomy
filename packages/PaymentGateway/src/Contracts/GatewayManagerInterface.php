<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;

/**
 * High-level manager for payment gateway operations.
 *
 * Provides a unified interface for payment operations across all gateways,
 * with built-in error handling, retries, and fallback support.
 */
interface GatewayManagerInterface
{
    /**
     * Register a gateway with credentials.
     */
    public function registerGateway(
        GatewayProvider $provider,
        GatewayCredentials $credentials,
    ): void;

    /**
     * Get a configured gateway instance.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function getGateway(GatewayProvider $provider): GatewayInterface;

    /**
     * Check if a gateway is registered.
     */
    public function hasGateway(GatewayProvider $provider): bool;

    /**
     * Authorize a payment using specified gateway.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\AuthorizationFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function authorize(
        GatewayProvider $provider,
        AuthorizeRequest $request,
    ): AuthorizationResult;

    /**
     * Capture a previously authorized payment.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\CaptureFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function capture(
        GatewayProvider $provider,
        CaptureRequest $request,
    ): CaptureResult;

    /**
     * Refund a captured payment.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\RefundFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function refund(
        GatewayProvider $provider,
        RefundRequest $request,
    ): RefundResult;

    /**
     * Void/cancel an authorization.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\VoidFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayNotFoundException
     */
    public function void(
        GatewayProvider $provider,
        VoidRequest $request,
    ): VoidResult;

    /**
     * Get all registered gateway providers.
     *
     * @return array<GatewayProvider>
     */
    public function getRegisteredProviders(): array;

    /**
     * Set the default gateway provider.
     */
    public function setDefaultProvider(GatewayProvider $provider): void;

    /**
     * Get the default gateway provider.
     */
    public function getDefaultProvider(): ?GatewayProvider;
}
