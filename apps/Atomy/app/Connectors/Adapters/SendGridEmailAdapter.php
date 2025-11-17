<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

/**
 * Example adapter for SendGrid Email.
 *
 * This demonstrates the plugin pattern - same interface, different implementation.
 * In production, install the SendGrid SDK and implement actual API calls.
 */
final readonly class SendGridEmailAdapter implements EmailServiceConnectorInterface
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
        // TODO: Implement actual SendGrid API call
        // Example using SendGrid SDK:
        //
        // $sendgrid = new \SendGrid($this->apiKey);
        //
        // $email = new \SendGrid\Mail\Mail();
        // $email->setFrom($this->fromEmail, $this->fromName);
        // $email->addTo($recipient);
        // $email->setSubject($subject);
        // $email->addContent('text/html', $body);
        //
        // $response = $sendgrid->send($email);
        // return $response->statusCode() === 202;

        // Placeholder for demonstration
        \Log::info("SendGridEmailAdapter: Sending email to {$recipient}", [
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

        // SendGrid supports batch sending - this is simplified
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
        // TODO: Implement actual SendGrid statistics API call
        return [
            'sent' => 0,
            'delivered' => 0,
            'bounced' => 0,
            'opened' => 0,
            'clicked' => 0,
        ];
    }
}
