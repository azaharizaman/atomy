<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

/**
 * Example adapter for Mailchimp Transactional Email (Mandrill).
 *
 * This demonstrates how to implement a vendor adapter.
 * In production, install the Mailchimp SDK and implement actual API calls.
 */
final readonly class MailchimpEmailAdapter implements EmailServiceConnectorInterface
{
    public function __construct(
        private string $apiKey,
        private string $fromEmail,
        private string $fromName,
    ) {}

    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        // TODO: Implement actual Mailchimp API call
        // Example using Mailchimp Transactional SDK:
        //
        // $mailchimp = new \MailchimpTransactional\ApiClient();
        // $mailchimp->setApiKey($this->apiKey);
        //
        // $message = [
        //     'html' => $body,
        //     'subject' => $subject,
        //     'from_email' => $this->fromEmail,
        //     'from_name' => $this->fromName,
        //     'to' => [['email' => $recipient]],
        // ];
        //
        // $response = $mailchimp->messages->send(['message' => $message]);
        // return $response[0]['status'] === 'sent';

        // Placeholder for demonstration
        \Log::info("MailchimpEmailAdapter: Sending email to {$recipient}", [
            'subject' => $subject,
            'from' => $this->fromEmail,
        ]);

        return true;
    }

    public function sendBulkEmail(array $emails, array $options = []): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($emails as $index => $email) {
            try {
                $success = $this->sendTransactionalEmail(
                    $email['recipient'],
                    $email['subject'],
                    $email['body'],
                    $options
                );

                if ($success) {
                    $sent++;
                } else {
                    $failed++;
                    $errors[$index] = 'Send failed';
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function validateAddress(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // TODO: Implement actual Mailchimp statistics API call
        return [
            'sent' => 0,
            'delivered' => 0,
            'bounced' => 0,
            'opened' => 0,
            'clicked' => 0,
        ];
    }
}
