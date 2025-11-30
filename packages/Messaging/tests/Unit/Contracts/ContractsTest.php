<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Contracts;

use Nexus\Messaging\Contracts\MessageTemplateEngineInterface;
use Nexus\Messaging\Contracts\MessagingConnectorInterface;
use Nexus\Messaging\Contracts\MessagingRepositoryInterface;
use Nexus\Messaging\Contracts\RateLimiterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Verify all contracts are properly defined
 */
final class ContractsTest extends TestCase
{
    public function testMessagingRepositoryInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(MessagingRepositoryInterface::class));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'saveRecord'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findById'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findByEntity'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findLatestByEntity'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findByTenant'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findBySender'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'findByChannel'));
        $this->assertTrue(method_exists(MessagingRepositoryInterface::class, 'countByEntity'));
    }

    public function testMessagingConnectorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(MessagingConnectorInterface::class));
        $this->assertTrue(method_exists(MessagingConnectorInterface::class, 'send'));
        $this->assertTrue(method_exists(MessagingConnectorInterface::class, 'processInboundWebhook'));
        $this->assertTrue(method_exists(MessagingConnectorInterface::class, 'getSupportedChannel'));
        $this->assertTrue(method_exists(MessagingConnectorInterface::class, 'isConfigured'));
    }

    public function testRateLimiterInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(RateLimiterInterface::class));
        $this->assertTrue(method_exists(RateLimiterInterface::class, 'allowAction'));
    }

    public function testMessageTemplateEngineInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(MessageTemplateEngineInterface::class));
        $this->assertTrue(method_exists(MessageTemplateEngineInterface::class, 'render'));
        $this->assertTrue(method_exists(MessageTemplateEngineInterface::class, 'renderSubject'));
    }
}
