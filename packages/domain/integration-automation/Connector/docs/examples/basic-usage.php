<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Connector
 * 
 * This example demonstrates:
 * 1. Sending transactional email
 * 2. Sending SMS notification
 * 3. Processing payment
 * 4. Automatic error handling with circuit breaker
 */

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Nexus\Connector\Contracts\SmsServiceConnectorInterface;
use Nexus\Connector\Contracts\PaymentGatewayConnectorInterface;
use Nexus\Connector\Exceptions\CircuitBreakerOpenException;
use Nexus\Connector\Exceptions\ConnectionException;

// ============================================
// Step 1: Send Transactional Email
// ============================================

final readonly class OrderConfirmationService
{
    public function __construct(
        private EmailServiceConnectorInterface $emailConnector
    ) {}
    
    public function send(string $customerEmail, string $orderNumber): void
    {
        try {
            $this->emailConnector->sendTransactionalEmail(
                recipient: $customerEmail,
                subject: "Order Confirmation #{$orderNumber}",
                body: "<h1>Thank you for your order!</h1><p>Order #{$orderNumber} confirmed.</p>"
            );
        } catch (CircuitBreakerOpenException $e) {
            // Email service is down - queue for later
            // Queue::push(new SendOrderEmailJob($customerEmail, $orderNumber));
        } catch (ConnectionException $e) {
            // All retries failed - log for manual review
            error_log("Failed to send order confirmation: " . $e->getMessage());
        }
    }
}

// Usage
// $service = new OrderConfirmationService($emailConnector);
// $service->send('customer@example.com', 'ORD-12345');

// ============================================
// Step 2: Send SMS Notification
// ============================================

final readonly class ShipmentNotificationService
{
    public function __construct(
        private SmsServiceConnectorInterface $smsConnector
    ) {}
    
    public function notifyShipment(string $phoneNumber, string $trackingNumber): void
    {
        try {
            $messageId = $this->smsConnector->send(
                phoneNumber: $phoneNumber,
                message: "Your order has been shipped! Track it at: https://track.me/{$trackingNumber}"
            );
            
            echo "SMS sent successfully. Message ID: {$messageId}\n";
        } catch (ConnectionException $e) {
            error_log("Failed to send SMS: " . $e->getMessage());
        }
    }
}

// Usage
// $service = new ShipmentNotificationService($smsConnector);
// $service->notifyShipment('+60123456789', 'TRK123456');

// ============================================
// Step 3: Process Payment
// ============================================

use Nexus\Connector\Exceptions\PaymentDeclinedException;

final readonly class PaymentProcessor
{
    public function __construct(
        private PaymentGatewayConnectorInterface $paymentGateway
    ) {}
    
    public function charge(float $amount, string $cardToken): array
    {
        try {
            $result = $this->paymentGateway->charge(
                amount: $amount,
                currency: 'MYR',
                paymentMethod: ['token' => $cardToken],
                options: ['description' => 'Order payment']
            );
            
            return [
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'receipt_url' => $result['receipt_url']
            ];
        } catch (PaymentDeclinedException $e) {
            return [
                'success' => false,
                'error' => 'Payment declined: ' . $e->getMessage()
            ];
        } catch (ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again later.'
            ];
        }
    }
}

// Usage
// $processor = new PaymentProcessor($paymentGateway);
// $result = $processor->charge(99.99, 'tok_visa_1234');
// if ($result['success']) {
//     echo "Payment successful! Transaction ID: {$result['transaction_id']}\n";
// } else {
//     echo "Payment failed: {$result['error']}\n";
// }

// ============================================
// Step 4: Bulk Email Sending
// ============================================

final readonly class NewsletterService
{
    public function __construct(
        private EmailServiceConnectorInterface $emailConnector
    ) {}
    
    public function sendBulk(array $subscribers, string $newsletterHtml): array
    {
        $emails = [];
        
        foreach ($subscribers as $subscriber) {
            $emails[] = [
                'recipient' => $subscriber['email'],
                'subject' => 'Monthly Newsletter',
                'body' => $newsletterHtml
            ];
        }
        
        $result = $this->emailConnector->sendBulkEmail($emails);
        
        return [
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'success_rate' => ($result['sent'] / count($emails)) * 100
        ];
    }
}

// Usage
// $subscribers = [
//     ['email' => 'user1@example.com'],
//     ['email' => 'user2@example.com'],
// ];
// $service = new NewsletterService($emailConnector);
// $result = $service->sendBulk($subscribers, '<h1>Newsletter</h1>');
// echo "Sent: {$result['sent']}, Failed: {$result['failed']}, Success Rate: {$result['success_rate']}%\n";

echo "Basic usage examples completed successfully!\n";
