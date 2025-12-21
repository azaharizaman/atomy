<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\WebhookEventType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookEventType::class)]
final class WebhookEventTypeTest extends TestCase
{
    #[Test]
    public function it_has_expected_payment_events(): void
    {
        $paymentEvents = [
            WebhookEventType::PAYMENT_CREATED,
            WebhookEventType::PAYMENT_AUTHORIZED,
            WebhookEventType::PAYMENT_CAPTURED,
            WebhookEventType::PAYMENT_FAILED,
            WebhookEventType::PAYMENT_CANCELED,
        ];

        foreach ($paymentEvents as $event) {
            $this->assertTrue($event->isPaymentEvent());
        }
    }

    #[Test]
    public function it_has_expected_refund_events(): void
    {
        $refundEvents = [
            WebhookEventType::REFUND_CREATED,
            WebhookEventType::REFUND_COMPLETED,
            WebhookEventType::REFUND_FAILED,
        ];

        foreach ($refundEvents as $event) {
            $this->assertTrue($event->isRefundEvent());
        }
    }

    #[Test]
    public function it_has_expected_dispute_events(): void
    {
        $disputeEvents = [
            WebhookEventType::DISPUTE_CREATED,
            WebhookEventType::DISPUTE_WON,
            WebhookEventType::DISPUTE_LOST,
            WebhookEventType::DISPUTE_CLOSED,
        ];

        foreach ($disputeEvents as $event) {
            $this->assertTrue($event->isDisputeEvent());
        }
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Payment Created', WebhookEventType::PAYMENT_CREATED->label());
        $this->assertSame('Refund Completed', WebhookEventType::REFUND_COMPLETED->label());
        $this->assertSame('Dispute Won', WebhookEventType::DISPUTE_WON->label());
        $this->assertSame('Token Created', WebhookEventType::TOKEN_CREATED->label());
    }

    #[Test]
    public function non_payment_events_are_not_payment_events(): void
    {
        $this->assertFalse(WebhookEventType::REFUND_CREATED->isPaymentEvent());
        $this->assertFalse(WebhookEventType::DISPUTE_CREATED->isPaymentEvent());
        $this->assertFalse(WebhookEventType::TOKEN_CREATED->isPaymentEvent());
    }

    #[Test]
    public function non_refund_events_are_not_refund_events(): void
    {
        $this->assertFalse(WebhookEventType::PAYMENT_CAPTURED->isRefundEvent());
        $this->assertFalse(WebhookEventType::DISPUTE_CREATED->isRefundEvent());
    }

    #[Test]
    public function non_dispute_events_are_not_dispute_events(): void
    {
        $this->assertFalse(WebhookEventType::PAYMENT_CAPTURED->isDisputeEvent());
        $this->assertFalse(WebhookEventType::REFUND_COMPLETED->isDisputeEvent());
    }

    #[Test]
    public function it_has_correct_backing_values(): void
    {
        $this->assertSame('payment.created', WebhookEventType::PAYMENT_CREATED->value);
        $this->assertSame('refund.completed', WebhookEventType::REFUND_COMPLETED->value);
        $this->assertSame('dispute.won', WebhookEventType::DISPUTE_WON->value);
    }
}
