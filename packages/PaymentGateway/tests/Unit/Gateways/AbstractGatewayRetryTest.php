<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Gateways;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\AbstractGateway;
use Nexus\PaymentGateway\Contracts\CircuitBreakerInterface;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Exceptions\NetworkException;
use Nexus\PaymentGateway\Services\ExponentialBackoffStrategy;
use Nexus\PaymentGateway\Services\GatewayInvoker;
use Nexus\PaymentGateway\Services\NullCircuitBreaker;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use Nexus\PaymentGateway\ValueObjects\RefundResult;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use PHPUnit\Framework\TestCase;

class RetryableGateway extends AbstractGateway
{
    public int $attempts = 0;
    public bool $shouldFail = false;
    public bool $shouldFailForever = false;
    public ?AuthorizeRequest $lastRequest = null;

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::STRIPE;
    }

    public function getName(): string
    {
        return 'Retryable Gateway';
    }

    public function isInitialized(): bool
    {
        return $this->credentials !== null;
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
        return false;
    }

    protected function doAuthorize(AuthorizeRequest $request): AuthorizationResult
    {
        $this->attempts++;
        $this->lastRequest = $request;

        if ($this->shouldFailForever) {
            throw new NetworkException('Connection timed out');
        }

        if ($this->shouldFail && $this->attempts < 3) {
            throw new NetworkException('Connection timed out');
        }

        return new AuthorizationResult(
            success: true,
            transactionId: 'auth_123',
            authorizedAmount: $request->amount,
            rawResponse: []
        );
    }

    protected function doCapture(CaptureRequest $request): CaptureResult { throw new \Exception('Not implemented'); }
    protected function doRefund(RefundRequest $request): RefundResult { throw new \Exception('Not implemented'); }
    protected function doVoid(VoidRequest $request): VoidResult { throw new \Exception('Not implemented'); }
    protected function doGetStatus(): GatewayStatus { return GatewayStatus::HEALTHY; }
}

class AbstractGatewayRetryTest extends TestCase
{
    public function test_authorize_retries_on_network_exception()
    {
        $strategy = new ExponentialBackoffStrategy(maxAttempts: 3, baseDelayMs: 1);
        $invoker = new GatewayInvoker($strategy, new NullCircuitBreaker());
        
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->shouldFail = true;
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));

        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_123'
        );

        $result = $gateway->authorize($request);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $gateway->attempts);
    }

    public function test_authorize_fails_after_max_retries()
    {
        $strategy = new ExponentialBackoffStrategy(maxAttempts: 3, baseDelayMs: 1);
        $invoker = new GatewayInvoker($strategy, new NullCircuitBreaker());
        
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->shouldFailForever = true;
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));

        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_123'
        );

        $this->expectException(NetworkException::class);
        
        try {
            $gateway->authorize($request);
        } catch (NetworkException $e) {
            $this->assertEquals(3, $gateway->attempts);
            throw $e;
        }
    }

    public function test_authorize_propagates_idempotency_key()
    {
        $invoker = new GatewayInvoker(new ExponentialBackoffStrategy(), new NullCircuitBreaker());
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));

        $idempotencyKey = 'idemp_12345';
        $request = new AuthorizeRequest(
            amount: Money::of(100, 'USD'),
            paymentMethodToken: 'tok_123',
            idempotencyKey: $idempotencyKey
        );

        $gateway->authorize($request);

        $this->assertNotNull($gateway->lastRequest);
        $this->assertEquals($idempotencyKey, $gateway->lastRequest->idempotencyKey);
    }

    public function test_circuit_breaker_prevents_execution_when_open()
    {
        $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
        $circuitBreaker->method('isAvailable')->willReturn(false);

        $invoker = new GatewayInvoker(new ExponentialBackoffStrategy(), $circuitBreaker);
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));

        $request = new AuthorizeRequest(amount: Money::of(100, 'USD'), paymentMethodToken: 'tok_123');

        // Expect GatewayException because circuit is open
        // Note: AbstractGateway catches Throwable and rethrows AuthorizationFailedException wrapping the error
        // But GatewayInvoker throws GatewayException. AbstractGateway catches GatewayException and rethrows it.
        // So we expect GatewayException.
        $this->expectException(\Nexus\PaymentGateway\Exceptions\GatewayException::class);
        $this->expectExceptionMessage('Circuit breaker is open');

        $gateway->authorize($request);
    }

    public function test_circuit_breaker_reports_failure_on_exception()
    {
        $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
        $circuitBreaker->method('isAvailable')->willReturn(true);
        $circuitBreaker->expects($this->atLeastOnce())->method('reportFailure');

        // Use a strategy with 0 retries to fail fast
        $invoker = new GatewayInvoker(new ExponentialBackoffStrategy(maxAttempts: 1), $circuitBreaker);
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->shouldFailForever = true;
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));

        $request = new AuthorizeRequest(amount: Money::of(100, 'USD'), paymentMethodToken: 'tok_123');

        try {
            $gateway->authorize($request);
        } catch (NetworkException $e) {
            // Expected
        }
    }

    public function test_authorize_wraps_generic_exception()
    {
        $invoker = new GatewayInvoker(new ExponentialBackoffStrategy(), new NullCircuitBreaker());
        $gateway = new RetryableGateway(null, $invoker);
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));
        
        // Mock doAuthorize to throw generic exception
        $gateway = $this->getMockBuilder(RetryableGateway::class)
            ->setConstructorArgs([null, $invoker])
            ->onlyMethods(['doAuthorize'])
            ->getMock();
        
        $gateway->initialize(new GatewayCredentials(GatewayProvider::STRIPE, 'k', 's'));
        
        $gateway->method('doAuthorize')
            ->willThrowException(new \Exception('Something went wrong'));

        $request = new AuthorizeRequest(amount: Money::of(100, 'USD'), paymentMethodToken: 'tok_123');

        $this->expectException(\Nexus\PaymentGateway\Exceptions\AuthorizationFailedException::class);
        $this->expectExceptionMessage('Authorization failed due to gateway error');

        $gateway->authorize($request);
    }
}
