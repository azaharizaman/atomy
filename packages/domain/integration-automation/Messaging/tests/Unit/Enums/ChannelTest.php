<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Enums;

use Nexus\Messaging\Enums\Channel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\Enums\Channel
 */
final class ChannelTest extends TestCase
{
    public function test_all_channels_exist(): void
    {
        $this->assertInstanceOf(Channel::class, Channel::Email);
        $this->assertInstanceOf(Channel::class, Channel::SMS);
        $this->assertInstanceOf(Channel::class, Channel::PhoneCall);
        $this->assertInstanceOf(Channel::class, Channel::Chat);
        $this->assertInstanceOf(Channel::class, Channel::WhatsApp);
        $this->assertInstanceOf(Channel::class, Channel::iMessage);
        $this->assertInstanceOf(Channel::class, Channel::Webhook);
        $this->assertInstanceOf(Channel::class, Channel::InternalNote);
    }

    public function test_synchronous_channels(): void
    {
        $this->assertTrue(Channel::PhoneCall->isSynchronous());
        $this->assertTrue(Channel::Chat->isSynchronous());
        $this->assertTrue(Channel::WhatsApp->isSynchronous());
        $this->assertTrue(Channel::iMessage->isSynchronous());
    }

    public function test_asynchronous_channels(): void
    {
        $this->assertFalse(Channel::Email->isSynchronous());
        $this->assertFalse(Channel::SMS->isSynchronous());
        $this->assertFalse(Channel::Webhook->isSynchronous());
        $this->assertFalse(Channel::InternalNote->isSynchronous());
    }

    public function test_attachment_support(): void
    {
        $this->assertTrue(Channel::Email->supportsAttachments());
        $this->assertTrue(Channel::WhatsApp->supportsAttachments());
        $this->assertTrue(Channel::iMessage->supportsAttachments());
        
        $this->assertFalse(Channel::SMS->supportsAttachments());
        $this->assertFalse(Channel::PhoneCall->supportsAttachments());
    }

    public function test_encryption(): void
    {
        $this->assertTrue(Channel::WhatsApp->isEncrypted());
        $this->assertTrue(Channel::iMessage->isEncrypted());
        
        $this->assertFalse(Channel::Email->isEncrypted());
        $this->assertFalse(Channel::SMS->isEncrypted());
    }

    public function test_labels(): void
    {
        $this->assertSame('Email', Channel::Email->label());
        $this->assertSame('SMS', Channel::SMS->label());
        $this->assertSame('WhatsApp', Channel::WhatsApp->label());
        $this->assertSame('iMessage', Channel::iMessage->label());
    }
}
