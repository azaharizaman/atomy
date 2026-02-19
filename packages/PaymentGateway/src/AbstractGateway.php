<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway;

use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\EvidenceSubmissionRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\AuthorizationType;
use Nexus\PaymentGateway\Enums\CardBrand;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Services\GatewayInvoker;
use Nexus\PaymentGateway\Services\ExponentialBackoffStrategy;
use Nexus\PaymentGateway\Services\NullCircuitBreaker;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\EvidenceSubmissionResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract base class for payment gateway implementations.
 *
 * Provides common functionality and template method patterns
 * for concrete gateway implementations.
 */
abstract class AbstractGateway implements GatewayInterface
{
    protected ?GatewayCredentials $credentials = null;

    protected LoggerInterface $logger;

    protected GatewayInvoker $invoker;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?GatewayInvoker $invoker = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->invoker = $invoker ?? new GatewayInvoker(
            new ExponentialBackoffStrategy(),
            new NullCircuitBreaker()
        );
    }

    public function initialize(GatewayCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * Get the credentials.
     *
     * @throws \RuntimeException If credentials are not initialized
     */
    protected function getCredentials(): GatewayCredentials
    {
        if ($this->credentials === null) {
            throw new \RuntimeException('Gateway credentials not initialized');
        }

        return $this->credentials;
    }

    public function authorize(AuthorizeRequest $request): AuthorizationResult
    {
        $this->validateAuthorizeRequest($request);

        try {
            return $this->invoker->invoke(
                fn () => $this->doAuthorize($request),
                $this->getProvider()->value . '.authorize'
            );
        } catch (AuthorizationFailedException $e) {
            throw $e;
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Authorization failed', [
                'provider' => $this->getProvider()->value,
                'error' => $e->getMessage(),
            ]);

            throw AuthorizationFailedException::fromGatewayResponse(
                message: 'Authorization failed due to gateway error',
                errorCode: 'GATEWAY_ERROR',
                gatewayMessage: $e->getMessage(),
            );
        }
    }

    public function capture(CaptureRequest $request): CaptureResult
    {
        $this->validateCaptureRequest($request);

        try {
            return $this->invoker->invoke(
                fn () => $this->doCapture($request),
                $this->getProvider()->value . '.capture'
            );
        } catch (CaptureFailedException $e) {
            throw $e;
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Capture failed', [
                'provider' => $this->getProvider()->value,
                'error' => $e->getMessage(),
            ]);

            throw new CaptureFailedException(
                message: 'Capture failed due to gateway error',
                gatewayErrorCode: 'GATEWAY_ERROR',
                gatewayMessage: $e->getMessage(),
            );
        }
    }

    public function refund(RefundRequest $request): RefundResult
    {
        $this->validateRefundRequest($request);

        try {
            return $this->invoker->invoke(
                fn () => $this->doRefund($request),
                $this->getProvider()->value . '.refund'
            );
        } catch (RefundFailedException $e) {
            throw $e;
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Refund failed', [
                'provider' => $this->getProvider()->value,
                'error' => $e->getMessage(),
            ]);

            throw new RefundFailedException(
                message: 'Refund failed due to gateway error',
                gatewayErrorCode: 'GATEWAY_ERROR',
                gatewayMessage: $e->getMessage(),
            );
        }
    }

    public function void(VoidRequest $request): VoidResult
    {
        $this->validateVoidRequest($request);

        try {
            return $this->invoker->invoke(
                fn () => $this->doVoid($request),
                $this->getProvider()->value . '.void'
            );
        } catch (VoidFailedException $e) {
            throw $e;
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Void failed', [
                'provider' => $this->getProvider()->value,
                'error' => $e->getMessage(),
            ]);

            throw new VoidFailedException(
                message: 'Void failed due to gateway error',
                gatewayErrorCode: 'GATEWAY_ERROR',
                gatewayMessage: $e->getMessage(),
            );
        }
    }

    public function submitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult
    {
        $this->validateEvidenceSubmissionRequest($request);

        try {
            return $this->invoker->invoke(
                fn () => $this->doSubmitEvidence($request),
                $this->getProvider()->value . '.submit_evidence'
            );
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Evidence submission failed', [
                'provider' => $this->getProvider()->value,
                'dispute_id' => $request->disputeId,
                'error' => $e->getMessage(),
            ]);

            return EvidenceSubmissionResult::failure(
                'Evidence submission failed: ' . $e->getMessage()
            );
        }
    }

    public function getStatus(): GatewayStatus
    {
        try {
            return $this->doGetStatus();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get gateway status', [
                'provider' => $this->getProvider()->value,
                'error' => $e->getMessage(),
            ]);

            return GatewayStatus::UNHEALTHY;
        }
    }

    /**
     * Default supported card brands.
     *
     * Override in subclass for gateway-specific support.
     *
     * @return array<CardBrand>
     */
    public function getSupportedCardBrands(): array
    {
        return [
            CardBrand::VISA,
            CardBrand::MASTERCARD,
            CardBrand::AMEX,
        ];
    }

    /**
     * Default supported authorization types.
     *
     * Override in subclass for gateway-specific support.
     *
     * @return array<AuthorizationType>
     */
    public function getSupportedAuthorizationTypes(): array
    {
        return [
            AuthorizationType::PREAUTH,
            AuthorizationType::AUTH_CAPTURE,
        ];
    }

    /**
     * Check if gateway supports partial captures.
     *
     * Override in subclass for gateway-specific behavior.
     */
    public function supportsPartialCapture(): bool
    {
        return true;
    }

    /**
     * Check if gateway supports partial refunds.
     *
     * Override in subclass for gateway-specific behavior.
     */
    public function supportsPartialRefund(): bool
    {
        return true;
    }

    /**
     * Check if gateway supports void operations.
     *
     * Override in subclass for gateway-specific behavior.
     */
    public function supportsVoid(): bool
    {
        return true;
    }

    /**
     * Perform the actual authorization.
     *
     * @throws AuthorizationFailedException
     */
    abstract protected function doAuthorize(AuthorizeRequest $request): AuthorizationResult;

    /**
     * Perform the actual capture.
     *
     * @throws CaptureFailedException
     */
    abstract protected function doCapture(CaptureRequest $request): CaptureResult;

    /**
     * Perform the actual refund.
     *
     * @throws RefundFailedException
     */
    abstract protected function doRefund(RefundRequest $request): RefundResult;

    /**
     * Perform the actual void.
     *
     * @throws VoidFailedException
     */
    abstract protected function doVoid(VoidRequest $request): VoidResult;

    /**
     * Get the actual gateway status.
     */
    abstract protected function doGetStatus(): GatewayStatus;

    /**
     * Submit evidence for a dispute.
     *
     * @return EvidenceSubmissionResult
     */
    abstract protected function doSubmitEvidence(EvidenceSubmissionRequest $request): EvidenceSubmissionResult;

    /**
     * Validate authorization request.
     *
     * @throws AuthorizationFailedException
     */
    protected function validateAuthorizeRequest(AuthorizeRequest $request): void
    {
        if ($request->amount->getAmountInMinorUnits() <= 0) {
            throw AuthorizationFailedException::fromGatewayResponse(
                message: 'Amount must be greater than zero',
                errorCode: 'INVALID_AMOUNT',
                gatewayMessage: 'Amount must be greater than zero',
            );
        }

        if (empty($request->paymentMethodToken)) {
            throw AuthorizationFailedException::fromGatewayResponse(
                message: 'Payment method token is required',
                errorCode: 'INVALID_TOKEN',
                gatewayMessage: 'Payment method token is required',
            );
        }
    }

    /**
     * Validate capture request.
     *
     * @throws CaptureFailedException
     */
    protected function validateCaptureRequest(CaptureRequest $request): void
    {
        if (empty($request->authorizationId)) {
            throw new CaptureFailedException(
                message: 'Authorization ID is required',
                authorizationId: null,
                attemptedAmount: null,
                gatewayErrorCode: 'INVALID_AUTHORIZATION',
                gatewayMessage: 'Authorization ID is required',
            );
        }
    }

    /**
     * Validate refund request.
     *
     * @throws RefundFailedException
     */
    protected function validateRefundRequest(RefundRequest $request): void
    {
        if (empty($request->transactionId)) {
            throw new RefundFailedException(
                message: 'Transaction ID is required',
                transactionId: null,
                attemptedAmount: null,
                gatewayErrorCode: 'INVALID_TRANSACTION',
                gatewayMessage: 'Transaction ID is required for refund',
            );
        }
    }

    /**
     * Validate void request.
     *
     * @throws VoidFailedException
     */
    protected function validateVoidRequest(VoidRequest $request): void
    {
        if (empty($request->authorizationId)) {
            throw new VoidFailedException(
                message: 'Authorization ID is required',
                authorizationId: null,
                gatewayErrorCode: 'INVALID_AUTHORIZATION',
                gatewayMessage: 'Authorization ID is required',
            );
        }
    }

    /**
     * Validate evidence submission request.
     */
    protected function validateEvidenceSubmissionRequest(EvidenceSubmissionRequest $request): void
    {
        if (empty($request->disputeId)) {
            // Dispute ID is required but we don't throw - just log a warning
            $this->logger->warning('Evidence submission without dispute ID');
        }
    }

    /**
     * Generate a unique gateway transaction ID.
     */
    protected function generateTransactionId(): string
    {
        return sprintf(
            '%s_%s',
            strtoupper($this->getProvider()->value),
            bin2hex(random_bytes(16)),
        );
    }

    /**
     * Create a gateway error from exception.
     */
    protected function createErrorFromException(\Throwable $exception): GatewayError
    {
        return GatewayError::networkError($exception->getMessage());
    }
}
