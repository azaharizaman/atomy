<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Gateways;

use Nexus\PaymentGateway\AbstractGateway;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Psr\Log\LoggerInterface;

/**
 * Null gateway implementation (Null Object Pattern).
 *
 * All operations fail with clear error messages.
 * Useful for disabling payment processing in specific environments.
 */
final class NullGateway extends AbstractGateway
{
    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::STRIPE; // Default provider for null gateway
    }

    public function getName(): string
    {
        return 'Null Gateway (Disabled)';
    }

    public function isInitialized(): bool
    {
        return true; // Always initialized, but operations fail
    }

    public function supports3ds(): bool
    {
        return false;
    }

    public function supportsTokenization(): bool
    {
        return false;
    }

    public function supportsPartialCapture(): bool
    {
        return false;
    }

    public function supportsPartialRefund(): bool
    {
        return false;
    }

    public function supportsVoid(): bool
    {
        return false;
    }

    protected function doGetStatus(): GatewayStatus
    {
        return GatewayStatus::UNAVAILABLE;
    }

    protected function doAuthorize(AuthorizeRequest $request): AuthorizationResult
    {
        throw AuthorizationFailedException::fromGatewayResponse(
            message: 'Payment gateway is disabled',
            errorCode: 'gateway_disabled',
            gatewayMessage: 'The payment gateway is currently disabled. Please contact support.',
        );
    }

    protected function doCapture(CaptureRequest $request): CaptureResult
    {
        throw new CaptureFailedException(
            message: 'Payment gateway is disabled',
            authorizationId: $request->authorizationId,
            attemptedAmount: $request->amount,
            gatewayErrorCode: 'gateway_disabled',
            gatewayMessage: 'The payment gateway is currently disabled. Please contact support.',
        );
    }

    protected function doRefund(RefundRequest $request): RefundResult
    {
        throw new RefundFailedException(
            message: 'Payment gateway is disabled',
            transactionId: $request->transactionId,
            attemptedAmount: $request->amount,
            gatewayErrorCode: 'gateway_disabled',
            gatewayMessage: 'The payment gateway is currently disabled. Please contact support.',
        );
    }

    protected function doVoid(VoidRequest $request): VoidResult
    {
        throw new VoidFailedException(
            message: 'Payment gateway is disabled',
            authorizationId: $request->authorizationId,
            gatewayErrorCode: 'gateway_disabled',
            gatewayMessage: 'The payment gateway is currently disabled. Please contact support.',
        );
    }
}
