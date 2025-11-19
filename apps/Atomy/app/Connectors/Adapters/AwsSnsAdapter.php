<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Nexus\Connector\Contracts\SmsServiceConnectorInterface;
use Nexus\Connector\Exceptions\ConnectionException;

/**
 * AWS SNS SMS adapter.
 *
 * This adapter translates the generic SmsServiceConnectorInterface
 * into AWS SNS-specific API calls.
 */
final class AwsSnsAdapter implements SmsServiceConnectorInterface
{
    private SnsClient $client;

    /**
     * @param string $accessKeyId AWS access key ID
     * @param string $secretAccessKey AWS secret access key
     * @param string $region AWS region (default: us-east-1)
     */
    public function __construct(
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $region = 'us-east-1'
    ) {
        $this->client = new SnsClient([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->accessKeyId,
                'secret' => $this->secretAccessKey,
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function send(string $phoneNumber, string $message, array $options = []): string
    {
        try {
            $params = [
                'PhoneNumber' => $phoneNumber,
                'Message' => $message,
            ];

            // Set SMS type (Promotional or Transactional)
            $smsType = $options['sms_type'] ?? 'Transactional';
            $params['MessageAttributes'] = [
                'AWS.SNS.SMS.SMSType' => [
                    'DataType' => 'String',
                    'StringValue' => $smsType,
                ],
            ];

            // Set sender ID if provided
            if (isset($options['sender_id'])) {
                $params['MessageAttributes']['AWS.SNS.SMS.SenderID'] = [
                    'DataType' => 'String',
                    'StringValue' => $options['sender_id'],
                ];
            }

            $result = $this->client->publish($params);

            return $result->get('MessageId');

        } catch (AwsException $e) {
            throw ConnectionException::requestFailed(
                message: "AWS SNS send failed: {$e->getAwsErrorMessage()}",
                httpStatusCode: $e->getStatusCode(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendBulk(array $messages, array $options = []): array
    {
        $sent = 0;
        $failed = 0;
        $messageIds = [];

        foreach ($messages as $sms) {
            try {
                $messageId = $this->send(
                    $sms['phone'],
                    $sms['message'],
                    $options
                );
                
                $messageIds[] = $messageId;
                $sent++;
            } catch (\Throwable $e) {
                $messageIds[] = null;
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'message_ids' => $messageIds,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validatePhoneNumber(string $phoneNumber): array
    {
        // AWS SNS doesn't have a dedicated phone validation API
        // This is a basic E.164 format check
        $isValid = preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber) === 1;

        $countryCode = null;
        if ($isValid && preg_match('/^\+(\d{1,3})/', $phoneNumber, $matches)) {
            $countryCode = $matches[1];
        }

        return [
            'valid' => $isValid,
            'country_code' => $countryCode,
            'carrier' => null, // SNS doesn't provide carrier info
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function checkBalance(): array
    {
        try {
            // AWS SNS uses pay-as-you-go pricing
            // Getting spending info requires CloudWatch/Cost Explorer API
            // For now, return a placeholder
            $result = $this->client->getSMSAttributes([
                'attributes' => ['MonthlySpendLimit'],
            ]);

            $spendLimit = $result->get('attributes')['MonthlySpendLimit'] ?? 'unlimited';

            return [
                'balance' => 0.0, // AWS doesn't have a traditional balance
                'currency' => 'USD',
                'monthly_spend_limit' => $spendLimit,
            ];

        } catch (AwsException $e) {
            throw ConnectionException::requestFailed(
                message: "AWS SNS balance check failed: {$e->getAwsErrorMessage()}",
                httpStatusCode: $e->getStatusCode(),
                previous: $e
            );
        }
    }
}
