<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Contracts\GatewayManagerInterface;
use Nexus\PaymentGateway\Contracts\GatewayRegistryInterface;
use Nexus\PaymentGateway\Contracts\IdempotencyManagerInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\EvidenceSubmissionRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Events\GatewayErrorEvent;
use Nexus\PaymentGateway\Events\PaymentAuthorizedEvent;
use Nexus\PaymentGateway\Events\PaymentCapturedEvent;
use Nexus\PaymentGateway\Events\PaymentFailedEvent;
use Nexus\PaymentGateway\Events\PaymentRefundedEvent;
use Nexus\PaymentGateway\Events\PaymentVoidedEvent;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\EvidenceSubmissionResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\Contracts\GatewaySelectorInterface;
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
 *
 * Note: This class intentionally uses mutable state for runtime gateway
 * registration. Gateways are registered at application bootstrap, not
 * per-request. The state is application-scoped, not request-scoped.
 */
final class GatewayManager implements GatewayManagerInterface
{
    /**
     * Registered gateways by provider.
     *
     * @var array<string, GatewayInterface>
     */
    private array $gateways = [];

    /**
     * Default provider for operations when not explicitly specified.
     */
    private ?GatewayProvider $defaultProvider = null;

    public function __construct(
        private readonly GatewayRegistryInterface $registry,
        private readonly TenantContextInterface $tenantContext,
        private readonly ?IdempotencyManagerInterface $idempotencyManager = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly ?GatewaySelectorInterface $selector = null,
    ) {
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
        ?GatewayProvider $provider,
        AuthorizeRequest $request,
    ): AuthorizationResult {
        $provider = $provider ?? $this->resolveProvider($request);
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $operation = function () use ($gateway, $provider, $request, $tenantId) {
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
                } else {
                    $this->eventDispatcher?->dispatch(
                        PaymentFailedEvent::fromResult(
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
        };

        if ($this->idempotencyManager && $request->idempotencyKey) {
            /** @var AuthorizationResult */
            return $this->idempotencyManager->execute(
                provider: $provider,
                key: $request->idempotencyKey,
                operation: $operation,
                resultClass: AuthorizationResult::class
            );
        }

        return $operation();
    }

    public function capture(
        GatewayProvider $provider,
        CaptureRequest $request,
    ): CaptureResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $operation = function () use ($gateway, $provider, $request, $tenantId) {
            try {
                $result = $gateway->capture($request);

                $this->logger->info('Payment captured', [
                    'provider' => $provider->value,
                    'authorization_id' => $request->authorizationId,
                    'capture_id' => $result->captureId,
                    'amount' => $result->capturedAmount?->format(),
                    'final_capture' => $request->finalCapture,
                ]);

                if ($result->success) {
                    $this->eventDispatcher?->dispatch(
                        PaymentCapturedEvent::fromResult(
                            tenantId: $tenantId,
                            authorizationId: $request->authorizationId,
                            transactionReference: '',
                            provider: $provider,
                            result: $result,
                            finalCapture: $request->finalCapture,
                        )
                    );
                }

                return $result;
            } catch (CaptureFailedException $e) {
                $this->logGatewayError($tenantId, $provider, 'capture', $e, $request->authorizationId);
                throw $e;
            }
        };

        if ($this->idempotencyManager && $request->idempotencyKey) {
            /** @var CaptureResult */
            return $this->idempotencyManager->execute(
                provider: $provider,
                key: $request->idempotencyKey,
                operation: $operation,
                resultClass: CaptureResult::class
            );
        }

        return $operation();
    }

    public function refund(
        GatewayProvider $provider,
        RefundRequest $request,
    ): RefundResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $operation = function () use ($gateway, $provider, $request, $tenantId) {
            try {
                $result = $gateway->refund($request);

                $this->logger->info('Payment refunded', [
                    'provider' => $provider->value,
                    'transaction_id' => $request->transactionId,
                    'refund_id' => $result->refundId,
                    'amount' => $result->refundedAmount?->format(),
                    'type' => $result->type->value,
                    'reason' => $request->reason,
                ]);

                if ($result->success) {
                    $this->eventDispatcher?->dispatch(
                        PaymentRefundedEvent::fromResult(
                            tenantId: $tenantId,
                            captureId: $request->transactionId,
                            transactionReference: '',
                            provider: $provider,
                            result: $result,
                            reason: $request->reason,
                        )
                    );
                }

                return $result;
            } catch (RefundFailedException $e) {
                $this->logGatewayError($tenantId, $provider, 'refund', $e, $request->transactionId);
                throw $e;
            }
        };

        if ($this->idempotencyManager && $request->idempotencyKey) {
            /** @var RefundResult */
            return $this->idempotencyManager->execute(
                provider: $provider,
                key: $request->idempotencyKey,
                operation: $operation,
                resultClass: RefundResult::class
            );
        }

        return $operation();
    }

    public function void(
        GatewayProvider $provider,
        VoidRequest $request,
    ): VoidResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $operation = function () use ($gateway, $provider, $request, $tenantId) {
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
        };

        if ($this->idempotencyManager && $request->idempotencyKey) {
            /** @var VoidResult */
            return $this->idempotencyManager->execute(
                provider: $provider,
                key: $request->idempotencyKey,
                operation: $operation,
                resultClass: VoidResult::class
            );
        }

        return $operation();
    }

    public function submitEvidence(
        GatewayProvider $provider,
        EvidenceSubmissionRequest $request,
    ): EvidenceSubmissionResult {
        $gateway = $this->getGateway($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        try {
            $result = $gateway->submitEvidence($request);

            $this->logger->info('Evidence submitted', [
                'provider' => $provider->value,
                'dispute_id' => $request->disputeId,
                'submission_id' => $result->submissionId,
                'status' => $result->status,
            ]);

            return $result;
        } catch (GatewayException $e) {
            $this->logGatewayError($tenantId, $provider, 'submit_evidence', $e, $request->disputeId);
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

    private function resolveProvider(AuthorizeRequest $request): GatewayProvider
    {
        if ($this->selector) {
            return $this->selector->select($request);
        }

        if ($this->defaultProvider) {
            return $this->defaultProvider;
        }

        throw new GatewayException('No gateway provider specified and no selector/default available.');
    }
}
