<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Domain interface for email service providers.
 *
 * Vendors: Mailchimp, SendGrid, Amazon SES, Postmark, etc.
 */
interface EmailServiceConnectorInterface
{
    /**
     * Send a transactional email.
     *
     * @param string $recipient Email address of the recipient
     * @param string $subject Email subject line
     * @param string $body Email body (HTML or plain text)
     * @param array<string, mixed> $options Additional options (cc, bcc, attachments, etc.)
     * @return bool True if email was successfully sent
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool;

    /**
     * Send bulk emails to multiple recipients.
     *
     * @param array<int, array{recipient: string, subject: string, body: string}> $emails Array of email data
     * @param array<string, mixed> $options Additional options
     * @return array{sent: int, failed: int, errors: array<int, string>} Send results
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function sendBulkEmail(array $emails, array $options = []): array;

    /**
     * Validate an email address.
     *
     * @param string $email Email address to validate
     * @return bool True if email is valid
     */
    public function validateAddress(string $email): bool;

    /**
     * Get email sending statistics.
     *
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array{sent: int, delivered: int, bounced: int, opened: int, clicked: int}
     */
    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array;
}
