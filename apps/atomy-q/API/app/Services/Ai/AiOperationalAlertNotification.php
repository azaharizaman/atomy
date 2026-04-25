<?php

declare(strict_types=1);

namespace App\Services\Ai;

use InvalidArgumentException;

use App\Services\Ai\Exceptions\UnsupportedNotificationChannelException;
use Nexus\Notifier\Services\AbstractNotification;

final readonly class AiOperationalAlertNotification extends AbstractNotification
{
    private string $featureKey;

    private string $capabilityGroup;

    private string $status;

    /**
     * @var list<string>
     */
    private array $reasonCodes;

    /**
     * @var array<string, scalar|null>
     */
    private array $diagnostics;

    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        string $featureKey,
        string $capabilityGroup,
        string $status,
        array $reasonCodes,
        array $diagnostics,
    ) {
        $this->featureKey = $this->requireNonEmpty($featureKey, 'featureKey');
        $this->capabilityGroup = $this->requireNonEmpty($capabilityGroup, 'capabilityGroup');
        $this->status = $this->requireNonEmpty($status, 'status');
        $this->reasonCodes = $reasonCodes;
        $this->diagnostics = $diagnostics;
    }

    /**
     * @return array<string, mixed>
     */
    public function toEmail(): array
    {
        return [
            'subject' => sprintf(
                '[Atomy-Q][AI] %s is %s',
                $this->featureKey,
                strtoupper($this->status),
            ),
            'template' => 'ai-operational-alert',
            'data' => [
                'feature_key' => $this->featureKey,
                'capability_group' => $this->capabilityGroup,
                'status' => $this->status,
                'reason_codes' => $this->reasonCodes,
                'diagnostics' => $this->sanitizedDiagnostics(),
            ],
        ];
    }

    public function toSms(): string
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPush(): array
    {
        throw new UnsupportedNotificationChannelException('AI operational alerts do not support push delivery.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(): array
    {
        throw new UnsupportedNotificationChannelException('AI operational alerts do not support in-app delivery.');
    }

    private function requireNonEmpty(string $value, string $field): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('%s cannot be empty.', $field));
        }

        return $normalized;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function sanitizedDiagnostics(): array
    {
        $sanitized = [];

        foreach ([
            'mode',
            'status_hint',
            'reason_code',
            'reason_codes',
            'error_code',
            'endpoint_group',
            'provider',
            'model_id',
            'model_revision',
            'http_status',
            'retry_attempts',
            'attempts',
            'outcome',
        ] as $key) {
            if (! array_key_exists($key, $this->diagnostics)) {
                continue;
            }

            $value = $this->diagnostics[$key];
            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
