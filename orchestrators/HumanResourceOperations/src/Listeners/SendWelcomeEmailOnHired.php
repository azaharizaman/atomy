<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Listeners;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener: Send welcome email when employee is hired.
 */
final readonly class SendWelcomeEmailOnHired
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the EmployeeHiredEvent.
     */
    public function handle(array $event): void
    {
        $this->logger->info('Sending welcome email to new employee', [
            'employee_id' => $event['employeeId'] ?? null,
            'email' => $event['email'] ?? null,
        ]);

        // Implementation: Use Nexus\Notifier to send email
    }
}
