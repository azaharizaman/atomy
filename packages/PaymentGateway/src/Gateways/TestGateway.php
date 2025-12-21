<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Gateways;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\AbstractGateway;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Enums\RefundType;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use Psr\Log\LoggerInterface;

/**
 * Test gateway implementation for testing and development.
 *
 * This gateway simulates payment operations without making actual API calls.
 * Use special token prefixes to control behavior:
 *
 * - `tok_success_*` - Successful operations
 * - `tok_decline_*` - Declined transactions
 * - `tok_error_*` - Gateway errors
 * - `tok_3ds_*` - Requires 3DS authentication
 */
final class TestGateway extends AbstractGateway
{
    public const TOKEN_SUCCESS = 'tok_success';

    public const TOKEN_DECLINE = 'tok_decline';

    public const TOKEN_ERROR = 'tok_error';

    public const TOKEN_3DS = 'tok_3ds';

    private GatewayStatus $status = GatewayStatus::HEALTHY;

    /** @var array<string, array<string, mixed>> */
    private array $authorizations = [];

    /** @var array<string, array<string, mixed>> */
    private array $captures = [];

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::STRIPE; // Simulate Stripe for testing
    }

    public function getName(): string
    {
        return 'Test Gateway';
    }

    public function isInitialized(): bool
    {
        return $this->credentials !== null;
    }

    public function supports3ds(): bool
    {
        return true;
    }

    public function supportsTokenization(): bool
    {
        return true;
    }

    /**
     * Set the gateway status for testing.
     */
    public function setStatus(GatewayStatus $status): void
    {
        $this->status = $status;
    }

    protected function doGetStatus(): GatewayStatus
    {
        return $this->status;
    }

    protected function doAuthorize(AuthorizeRequest $request): AuthorizationResult
    {
        $token = $request->paymentMethodToken;

        // Check for decline
        if (str_starts_with($token, self::TOKEN_DECLINE)) {
            throw AuthorizationFailedException::fromDecline(
                declineCode: 'card_declined',
                declineReason: 'Your card was declined.',
                transactionId: $this->generateTransactionId(),
            );
        }

        // Check for gateway error
        if (str_starts_with($token, self::TOKEN_ERROR)) {
            throw AuthorizationFailedException::fromGatewayResponse(
                message: 'Gateway error occurred',
                errorCode: 'gateway_error',
                gatewayMessage: 'Test gateway error simulation',
            );
        }

        // Check for 3DS requirement
        if (str_starts_with($token, self::TOKEN_3DS)) {
            $authId = 'auth_' . bin2hex(random_bytes(12));

            return AuthorizationResult::requires3dsAuthentication(
                authorizationId: $authId,
                threeDsUrl: 'https://3ds.test/authenticate/' . $authId,
                rawResponse: ['type' => '3ds_required'],
            );
        }

        // Success case
        $authId = 'auth_' . bin2hex(random_bytes(12));
        $transactionId = $this->generateTransactionId();

        // Store authorization for later capture/void
        $this->authorizations[$authId] = [
            'amount' => $request->amount,
            'status' => TransactionStatus::AUTHORIZED,
            'transactionId' => $transactionId,
            'createdAt' => new \DateTimeImmutable(),
            'captured' => false,
            'capturedAmount' => Money::zero($request->amount->getCurrency()),
        ];

        // Handle auto-capture
        if ($request->isAutoCapture()) {
            $this->authorizations[$authId]['status'] = TransactionStatus::CAPTURED;
            $this->authorizations[$authId]['captured'] = true;
            $this->authorizations[$authId]['capturedAmount'] = $request->amount;

            return new AuthorizationResult(
                success: true,
                authorizationId: $authId,
                transactionId: $transactionId,
                status: TransactionStatus::CAPTURED,
                authorizedAmount: $request->amount,
                expiresAt: null,
                rawResponse: [
                    'type' => 'auth_capture',
                    'test' => true,
                ],
            );
        }

        return AuthorizationResult::success(
            authorizationId: $authId,
            amount: $request->amount,
            transactionId: $transactionId,
            expiresAt: new \DateTimeImmutable('+7 days'),
            rawResponse: ['type' => 'authorization', 'test' => true],
        );
    }

    protected function doCapture(CaptureRequest $request): CaptureResult
    {
        $authId = $request->authorizationId;

        // Check if authorization exists
        if (!isset($this->authorizations[$authId])) {
            throw new CaptureFailedException(
                message: 'Authorization not found',
                authorizationId: $authId,
                attemptedAmount: $request->amount,
                gatewayErrorCode: 'authorization_not_found',
                gatewayMessage: 'The authorization was not found or has expired',
            );
        }

        $auth = $this->authorizations[$authId];

        // Check if already captured
        if ($auth['captured']) {
            throw new CaptureFailedException(
                message: 'Authorization already captured',
                authorizationId: $authId,
                attemptedAmount: $request->amount,
                gatewayErrorCode: 'already_captured',
                gatewayMessage: 'This authorization has already been captured',
            );
        }

        // Determine capture amount
        /** @var Money $authorizedAmount */
        $authorizedAmount = $auth['amount'];
        $captureAmount = $request->amount ?? $authorizedAmount;

        // Validate amount
        if ($captureAmount->getAmountInMinorUnits() > $authorizedAmount->getAmountInMinorUnits()) {
            throw new CaptureFailedException(
                message: 'Capture amount exceeds authorization',
                authorizationId: $authId,
                attemptedAmount: $captureAmount,
                gatewayErrorCode: 'amount_too_large',
                gatewayMessage: 'Capture amount cannot exceed authorized amount',
            );
        }

        $captureId = 'cap_' . bin2hex(random_bytes(12));

        // Update authorization status
        $this->authorizations[$authId]['captured'] = true;
        $this->authorizations[$authId]['capturedAmount'] = $captureAmount;
        $this->authorizations[$authId]['status'] = $captureAmount->equals($authorizedAmount)
            ? TransactionStatus::CAPTURED
            : TransactionStatus::PARTIALLY_CAPTURED;

        // Store capture
        $this->captures[$captureId] = [
            'authorizationId' => $authId,
            'amount' => $captureAmount,
            'createdAt' => new \DateTimeImmutable(),
            'refundedAmount' => Money::zero($captureAmount->getCurrency()),
        ];

        return CaptureResult::success(
            captureId: $captureId,
            amount: $captureAmount,
            rawResponse: ['type' => 'capture', 'test' => true],
        );
    }

    protected function doRefund(RefundRequest $request): RefundResult
    {
        $captureId = $request->transactionId;

        // Check if capture exists
        if (!isset($this->captures[$captureId])) {
            throw new RefundFailedException(
                message: 'Capture not found',
                transactionId: $captureId,
                attemptedAmount: $request->amount,
                gatewayErrorCode: 'capture_not_found',
                gatewayMessage: 'The capture was not found',
            );
        }

        $capture = $this->captures[$captureId];

        /** @var Money $capturedAmount */
        $capturedAmount = $capture['amount'];
        /** @var Money $refundedAmount */
        $refundedAmount = $capture['refundedAmount'];

        // Determine refund amount
        $refundAmount = $request->amount;
        if ($refundAmount === null) {
            $refundAmount = $capturedAmount->subtract($refundedAmount);
        }

        // Calculate remaining
        $remaining = $capturedAmount->subtract($refundedAmount);

        // Validate refund amount
        if ($refundAmount->getAmountInMinorUnits() > $remaining->getAmountInMinorUnits()) {
            throw new RefundFailedException(
                message: 'Refund amount exceeds captured amount',
                transactionId: $captureId,
                attemptedAmount: $refundAmount,
                gatewayErrorCode: 'amount_too_large',
                gatewayMessage: 'Refund amount cannot exceed remaining captured amount',
            );
        }

        $refundId = 'ref_' . bin2hex(random_bytes(12));

        // Update capture
        $newRefundedAmount = $refundedAmount->add($refundAmount);
        $this->captures[$captureId]['refundedAmount'] = $newRefundedAmount;

        $refundType = $newRefundedAmount->equals($capturedAmount)
            ? RefundType::FULL
            : RefundType::PARTIAL;

        return RefundResult::success(
            refundId: $refundId,
            amount: $refundAmount,
            type: $refundType,
            rawResponse: ['type' => 'refund', 'test' => true],
        );
    }

    protected function doVoid(VoidRequest $request): VoidResult
    {
        $authId = $request->authorizationId;

        // Check if authorization exists
        if (!isset($this->authorizations[$authId])) {
            throw new VoidFailedException(
                message: 'Authorization not found',
                authorizationId: $authId,
                gatewayErrorCode: 'authorization_not_found',
                gatewayMessage: 'The authorization was not found or has expired',
            );
        }

        $auth = $this->authorizations[$authId];

        // Check if already captured
        if ($auth['captured']) {
            throw new VoidFailedException(
                message: 'Cannot void captured authorization',
                authorizationId: $authId,
                gatewayErrorCode: 'already_captured',
                gatewayMessage: 'This authorization has been captured and cannot be voided. Use refund instead.',
            );
        }

        $voidId = 'void_' . bin2hex(random_bytes(12));

        // Update authorization
        $this->authorizations[$authId]['status'] = TransactionStatus::VOIDED;

        return VoidResult::success(
            voidId: $voidId,
            rawResponse: ['type' => 'void', 'test' => true],
        );
    }

    /**
     * Get stored authorizations (for testing assertions).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAuthorizations(): array
    {
        return $this->authorizations;
    }

    /**
     * Get stored captures (for testing assertions).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getCaptures(): array
    {
        return $this->captures;
    }

    /**
     * Clear all stored transactions (for test isolation).
     */
    public function reset(): void
    {
        $this->authorizations = [];
        $this->captures = [];
    }
}
