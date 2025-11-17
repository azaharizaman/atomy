<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Domain interface for SMS service providers.
 *
 * Vendors: Twilio, Nexmo/Vonage, MessageBird, etc.
 */
interface SmsServiceConnectorInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $phoneNumber Recipient phone number (E.164 format)
     * @param string $message Message content
     * @param array<string, mixed> $options Additional options (sender_id, etc.)
     * @return string Message ID from the provider
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function send(string $phoneNumber, string $message, array $options = []): string;

    /**
     * Send bulk SMS messages.
     *
     * @param array<int, array{phone: string, message: string}> $messages Array of SMS data
     * @param array<string, mixed> $options Additional options
     * @return array{sent: int, failed: int, message_ids: array<int, string>}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function sendBulk(array $messages, array $options = []): array;

    /**
     * Validate a phone number.
     *
     * @param string $phoneNumber Phone number to validate
     * @return array{valid: bool, country_code: ?string, carrier: ?string}
     */
    public function validatePhoneNumber(string $phoneNumber): array;

    /**
     * Check account balance/credits.
     *
     * @return array{balance: float, currency: string}
     */
    public function checkBalance(): array;
}
