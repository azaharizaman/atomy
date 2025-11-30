<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Exceptions;

use Nexus\Messaging\Exceptions\InvalidChannelException;
use Nexus\Messaging\Exceptions\MessageDeliveryException;
use Nexus\Messaging\Exceptions\MessageNotFoundException;
use Nexus\Messaging\Exceptions\MessagingException;
use Nexus\Messaging\Exceptions\RateLimitExceededException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\Exceptions\MessagingException
 * @covers \Nexus\Messaging\Exceptions\MessageNotFoundException
 * @covers \Nexus\Messaging\Exceptions\MessageDeliveryException
 * @covers \Nexus\Messaging\Exceptions\RateLimitExceededException
 * @covers \Nexus\Messaging\Exceptions\InvalidChannelException
 */
final class ExceptionsTest extends TestCase
{
    public function testMessagingException(): void
    {
        $exception = new MessagingException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testMessageNotFoundException(): void
    {
        $exception = new MessageNotFoundException('Message not found');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertSame('Message not found', $exception->getMessage());
    }

    public function testMessageNotFoundExceptionWithId(): void
    {
        $exception = MessageNotFoundException::withId('msg-123');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString('msg-123', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testMessageDeliveryException(): void
    {
        $exception = new MessageDeliveryException('Delivery failed');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertSame('Delivery failed', $exception->getMessage());
    }

    public function testMessageDeliveryExceptionForMessage(): void
    {
        $exception = MessageDeliveryException::forMessage('msg-456', 'Network timeout');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString('msg-456', $exception->getMessage());
        $this->assertStringContainsString('Network timeout', $exception->getMessage());
        $this->assertStringContainsString('Failed to deliver', $exception->getMessage());
    }

    public function testMessageDeliveryExceptionProviderError(): void
    {
        $exception = MessageDeliveryException::providerError('Twilio', 'Invalid credentials');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString('Twilio', $exception->getMessage());
        $this->assertStringContainsString('Invalid credentials', $exception->getMessage());
    }

    public function testRateLimitExceededException(): void
    {
        $exception = new RateLimitExceededException('Rate limit exceeded');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertSame('Rate limit exceeded', $exception->getMessage());
    }

    public function testRateLimitExceededExceptionForTenant(): void
    {
        $exception = RateLimitExceededException::forTenant('tenant-001', 100);
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString('tenant-001', $exception->getMessage());
        $this->assertStringContainsString('100', $exception->getMessage());
        $this->assertStringContainsString('Rate limit', $exception->getMessage());
    }

    public function testRateLimitExceededExceptionForChannel(): void
    {
        $exception = RateLimitExceededException::forChannel('email', 50);
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString('email', $exception->getMessage());
        $this->assertStringContainsString('50', $exception->getMessage());
        $this->assertStringContainsString('Rate limit', $exception->getMessage());
    }

    public function testInvalidChannelException(): void
    {
        $exception = new InvalidChannelException('Invalid channel');
        
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertSame('Invalid channel', $exception->getMessage());
    }
}
