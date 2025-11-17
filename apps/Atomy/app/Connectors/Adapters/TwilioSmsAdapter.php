<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\SmsServiceConnectorInterface;

/**
 * Example adapter for Twilio SMS.
 *
 * This demonstrates SMS service implementation.
 * In production, install the Twilio SDK and implement actual API calls.
 */
final readonly class TwilioSmsAdapter implements SmsServiceConnectorInterface
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private string $fromNumber,
    ) {}

    public function send(string $phoneNumber, string $message, array $options = []): string
    {
        // TODO: Implement actual Twilio API call
        // Example using Twilio SDK:
        //
        // $twilio = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
        //
        // $twilioMessage = $twilio->messages->create(
        //     $phoneNumber,
        //     [
        //         'from' => $this->fromNumber,
        //         'body' => $message,
        //     ]
        // );
        //
        // return $twilioMessage->sid;

        // Placeholder for demonstration
        \Log::info("TwilioSmsAdapter: Sending SMS to {$phoneNumber}", [
            'message' => substr($message, 0, 50),
            'from' => $this->fromNumber,
        ]);

        return 'SM' . bin2hex(random_bytes(16)); // Mock message ID
    }

    public function sendBulk(array $messages, array $options = []): array
    {
        $sent = 0;
        $failed = 0;
        $messageIds = [];

        foreach ($messages as $sms) {
            try {
                $messageId = $this->send($sms['phone'], $sms['message'], $options);
                $messageIds[] = $messageId;
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $messageIds[] = null;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'message_ids' => $messageIds,
        ];
    }

    public function validatePhoneNumber(string $phoneNumber): array
    {
        // TODO: Implement actual Twilio phone number validation API
        // For now, basic validation
        $isValid = preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber) === 1;

        return [
            'valid' => $isValid,
            'country_code' => $isValid ? substr($phoneNumber, 0, 3) : null,
            'carrier' => null,
        ];
    }

    public function checkBalance(): array
    {
        // TODO: Implement actual Twilio balance API call
        return [
            'balance' => 0.0,
            'currency' => 'USD',
        ];
    }
}
