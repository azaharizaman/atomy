<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayManagerInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Events\GatewayErrorEvent;
use Nexus\PaymentGateway\Events\PaymentAuthorizedEvent;
use Nexus\PaymentGateway\Events\PaymentCapturedEvent;
use Nexus\PaymentGateway\Events\PaymentRefundedEvent;
use Nexus\PaymentGateway\Events\PaymentVoidedEvent;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * High-level manager for payment gateway operations.
 *
 * Provides unified interface for all gateway operations with
 * logging, event dispatching, and error handling.
 */
final readonly class GatewayManager implements GatewayManagerInterface
{
    /** @var array<string, GatewayInterface> */
    private array $gateways;

    private ?GatewayProvider $defaultProvider;

    public function __construct(
        private GatewayRegistryInterface $registry,
        private TenantContextInterface $tenantContext,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->gateways = [];
        $this->defaultProvider = null;
    }

    public function registerGateway(
        GatewayProvider $provider,
        GatewayCredentials $credentials,
    ): void {
        $gateway = $this->registry->create($provider);
        $gateway->initialize($credentials);

        $this->gateways[$provider->value] = $gateway;

        $this->logger->info('Gateway registered', [
            'provider' => $provider->value,
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
        ]);
    }

    public function getGateway(GatewayProvider $provider): GatewayInterface
    {
        if (!isset($this->gateways[$provider->value])) {
            throw new GatewayNotFoundException($provider);
        }

        return $this->gateways[$provider->value];
    }

    public function hasGateway(GatewayProvider $provider): bool
    {
        return isset($this->gateways[$provider->value]);
    }

    public function authorize(
        GatewayProvider $provider,
        AuthorizeRequest $request,
    ): AuthorizationResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        try {
            $result = $gateway->authorize($request);

            $this->logger->info('Payment authorized', [
                'provider' => $provider->value,
                'transaction_id' => $result->transactionId,
                'amount' => $request->amount->format(),
            ]);

            if ($result->success) {
                $this->eventDispatcher?->dispatch(
                    PaymentAuthorizedEvent::fromResult(
                        tenantId: $tenantId,
                        transactionReference: $request->orderId ?? '',
                        provider: $provider,
                        amount: $request->amount,
                        result: $result,
                    )
                );
            }

            return $result;
        } catch (AuthorizationFailedException $e) {
            $this->logGatewayError($tenantId, $provider, 'authorize', $e, $request->orderId);
            throw $e;
        }
    }

    public function capture(
        GatewayProvider $provider,
        CaptureRequest $request,
    ): CaptureResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        try {
            $result = $gateway->capture($request);

            $this->logger->info('Payment captured', [
                'provider' => $provider->value,
                'authorization_id' => $request->authorizationId,
                'capture_id' => $result->captureId,
                'amount' => $result->capturedAmount?->format(),
            ]);

            if ($result->success) {
                $this->eventDispatcher?->dispatch(
                    PaymentCapturedEvent::fromResult(
                        tenantId: $tenantId,
                        authorizationId: $request->authorizationId,
                        transactionReference: '',
                        provider: $provider,
                        result: $result,
                    )
                );
            }

            return $result;
        } catch (CaptureFailedException $e) {
            $this->logGatewayError($tenantId, $provider, 'capture', $e, $request->authorizationId);
            throw $e;
        }
    }

    public function refund(
        GatewayProvider $provider,
        RefundRequest $request,
    ): RefundResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        try {
            $result = $gateway->refund($request);

            $this->logger->info('Payment refunded', [
                'provider' => $provider->value,
                'transaction_id' => $request->transactionId,
                'refund_id' => $result->refundId,
                'amount' => $result->refundedAmount?->format(),
                'type' => $result->type->value,
            ]);

            if ($result->success) {
                $this->eventDispatcher?->dispatch(
                    PaymentRefundedEvent::fromResult(
                        tenantId: $tenantId,
                        captureId: $request->transactionId,
                        transactionReference: '',
                        provider: $provider,
                        result: $result,
                    )
                );
            }

            return $result;
        } catch (RefundFailedException $e) {
            $this->logGatewayError($tenantId, $provider, 'refund', $e, $request->transactionId);
            throw $e;
        }
    }

    public function void(
        GatewayProvider $provider,
        VoidRequest $request,
    ): VoidResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        try {
            $result = $gateway->void($request);

            $this->logger->info('Authorization voided', [
                'provider' => $provider->value,
                'authorization_id' => $request->authorizationId,
                'void_id' => $result->voidId,
            ]);

            if ($result->success) {
                $this->eventDispatcher?->dispatch(
                    PaymentVoidedEvent::fromResult(
                        tenantId: $tenantId,
                        authorizationId: $request->authorizationId,
                        transactionReference: '',
                        provider: $provider,
                        result: $result,
                    )
                );
            }

            return $result;
        } catch (VoidFailedException $e) {
            $this->logGatewayError($tenantId, $provider, 'void', $e, $request->authorizationId);
            throw $e;
        }
    }

    public function getRegisteredProviders(): array
    {
        return array_map(
            fn(string $key) => GatewayProvider::from($key),
            array_keys($this->gateways),
        );
    }

    public function setDefaultProvider(GatewayProvider $provider): void
    {
        if (!$this->hasGateway($provider)) {
            throw new GatewayNotFoundException($provider);
        }

        $this->defaultProvider = $provider;
    }

    public function getDefaultProvider(): ?GatewayProvider
    {
        return $this->defaultProvider;
    }

    private function logGatewayError(
        string $tenantId,
        GatewayProvider $provider,
        string $operation,
        \Throwable $exception,
        ?string $transactionReference,
    ): void {
        $this->logger->error('Gateway operation failed', [
            'provider' => $provider->value,
            'operation' => $operation,
            'transaction_reference' => $transactionReference,
            'error' => $exception->getMessage(),
        ]);

        $this->eventDispatcher?->dispatch(
            GatewayErrorEvent::fromError(
                tenantId: $tenantId,
                provider: $provider,
                operation: $operation,
                error: GatewayError::networkError($exception->getMessage()),
                transactionReference: $transactionReference,
            )
        );
    }
}
