<?php

declare(strict_types=1);

namespace Nexus\Notifier\Tests\ValueObjects;

use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\NotificationContent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotificationContent::class)]
final class NotificationContentTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_data(): void
    {
        $content = new NotificationContent(
            emailData: ['subject' => 'Test', 'body' => 'Test Body']
        );

        $this->assertSame(['subject' => 'Test', 'body' => 'Test Body'], $content->emailData);
        $this->assertNull($content->smsText);
        $this->assertNull($content->pushData);
        $this->assertNull($content->inAppData);
    }

    #[Test]
    public function it_can_be_created_with_all_channels(): void
    {
        $content = new NotificationContent(
            emailData: ['subject' => 'Test Subject', 'body' => 'Email Body'],
            smsText: 'SMS Text',
            pushData: ['title' => 'Push Title', 'body' => 'Push Body'],
            inAppData: ['title' => 'App Title', 'message' => 'App Message']
        );

        $this->assertSame(['subject' => 'Test Subject', 'body' => 'Email Body'], $content->emailData);
        $this->assertSame('SMS Text', $content->smsText);
        $this->assertSame(['title' => 'Push Title', 'body' => 'Push Body'], $content->pushData);
        $this->assertSame(['title' => 'App Title', 'message' => 'App Message'], $content->inAppData);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $content = new NotificationContent(
            emailData: ['subject' => 'Test', 'body' => 'Test']
        );

        // Verify properties are readonly by checking reflection
        $reflection = new \ReflectionClass($content);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    #[Test]
    public function it_can_check_if_content_exists_for_channel(): void
    {
        $content = new NotificationContent(
            emailData: ['subject' => 'Test', 'body' => 'Test'],
            smsText: 'SMS Text'
        );

        $this->assertTrue($content->hasContentFor(ChannelType::Email));
        $this->assertTrue($content->hasContentFor(ChannelType::Sms));
        $this->assertFalse($content->hasContentFor(ChannelType::Push));
        $this->assertFalse($content->hasContentFor(ChannelType::InApp));
    }

    #[Test]
    public function it_can_get_content_for_specific_channel(): void
    {
        $emailData = ['subject' => 'Test', 'body' => 'Email'];
        $smsText = 'SMS Text';
        
        $content = new NotificationContent(
            emailData: $emailData,
            smsText: $smsText
        );

        $this->assertSame($emailData, $content->getContentFor(ChannelType::Email));
        $this->assertSame($smsText, $content->getContentFor(ChannelType::Sms));
        $this->assertNull($content->getContentFor(ChannelType::Push));
        $this->assertNull($content->getContentFor(ChannelType::InApp));
    }

    #[Test]
    public function it_can_get_available_channels(): void
    {
        $content = new NotificationContent(
            emailData: ['subject' => 'Test', 'body' => 'Test'],
            pushData: ['title' => 'Push', 'body' => 'Push Body']
        );

        $channels = $content->getAvailableChannels();
        
        $this->assertCount(2, $channels);
        $this->assertContains(ChannelType::Email, $channels);
        $this->assertContains(ChannelType::Push, $channels);
        $this->assertNotContains(ChannelType::Sms, $channels);
        $this->assertNotContains(ChannelType::InApp, $channels);
    }

    #[Test]
    public function it_handles_empty_email_data(): void
    {
        $content = new NotificationContent(
            emailData: []
        );

        $this->assertFalse($content->hasContentFor(ChannelType::Email));
        $this->assertSame([], $content->emailData);
    }
}
