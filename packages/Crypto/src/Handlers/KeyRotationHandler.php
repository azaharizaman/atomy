<?php

declare(strict_types=1);

namespace Nexus\Crypto\Handlers;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Nexus\Crypto\Contracts\KeyRotationServiceInterface;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Key Rotation Handler
 *
 * Scheduled job handler for automated encryption key rotation.
 * Checks for expiring keys and rotates them automatically.
 *
 * Integrates with Nexus\Scheduler for cron-based execution.
 */
final readonly class KeyRotationHandler implements JobHandlerInterface
{
    /**
     * Job type for key rotation
     */
    private const JOB_TYPE = 'crypto_key_rotation';
    
    public function __construct(
        private KeyRotationServiceInterface $keyRotationService,
        private LoggerInterface $logger,
    ) {}
    
    /**
     * {@inheritdoc}
     */
    public function supports(JobType $jobType): bool
    {
        return $jobType->value === self::JOB_TYPE;
    }
    
    /**
     * {@inheritdoc}
     */
    public function handle(ScheduledJob $job): JobResult
    {
        $startTime = microtime(true);
        $rotatedKeys = [];
        $errors = [];
        
        try {
            // Get warning threshold from payload (default: 7 days)
            $warningDays = $job->payload['warningDays'] ?? 7;
            
            // Find keys expiring soon
            $expiringKeyIds = $this->keyRotationService->findExpiringKeys($warningDays);
            
            $this->logger->info('Key rotation check started', [
                'jobId' => $job->id,
                'expiringKeysFound' => count($expiringKeyIds),
                'warningDays' => $warningDays,
            ]);
            
            // Rotate each expiring key
            foreach ($expiringKeyIds as $keyId) {
                try {
                    $newKey = $this->keyRotationService->rotateKey($keyId);
                    
                    $rotatedKeys[] = [
                        'keyId' => $keyId,
                        'newExpiresAt' => $newKey->expiresAt?->format('c'),
                    ];
                    
                    $this->logger->info('Key rotated successfully', [
                        'keyId' => $keyId,
                        'expiresAt' => $newKey->expiresAt?->format('c'),
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = [
                        'keyId' => $keyId,
                        'error' => $e->getMessage(),
                    ];
                    
                    $this->logger->error('Key rotation failed', [
                        'keyId' => $keyId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $duration = microtime(true) - $startTime;
            
            // Determine success/failure
            if (empty($errors)) {
                return JobResult::success(
                    output: [
                        'rotatedCount' => count($rotatedKeys),
                        'rotatedKeys' => $rotatedKeys,
                        'duration' => round($duration, 3),
                    ]
                );
            }
            
            // Partial success - some keys failed
            if (!empty($rotatedKeys)) {
                $this->logger->warning('Key rotation completed with errors', [
                    'rotated' => count($rotatedKeys),
                    'failed' => count($errors),
                ]);
                
                // Retry only failed keys
                return JobResult::failure(
                    error: sprintf('%d key(s) failed to rotate', count($errors)),
                    shouldRetry: true,
                    retryDelaySeconds: 300, // 5 minutes
                );
            }
            
            // Complete failure
            return JobResult::failure(
                error: 'All key rotations failed',
                shouldRetry: true,
                retryDelaySeconds: 600, // 10 minutes
            );
        } catch (\Throwable $e) {
            $this->logger->error('Key rotation job failed catastrophically', [
                'jobId' => $job->id,
                'error' => $e->getMessage(),
            ]);
            
            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true,
                retryDelaySeconds: 600,
            );
        }
    }
    
    /**
     * Create a schedule definition for daily key rotation checks
     *
     * Convenience method for setting up automated rotation.
     *
     * @param int $warningDays Rotate keys expiring within this many days
     * @return array<string, mixed> Schedule definition data
     */
    public static function createDailySchedule(int $warningDays = 7): array
    {
        return [
            'jobType' => self::JOB_TYPE,
            'targetId' => 'system',
            'runAt' => new DateTimeImmutable('tomorrow 03:00'), // Run at 3 AM
            'recurrence' => [
                'type' => 'daily',
                'interval' => 1,
            ],
            'payload' => [
                'warningDays' => $warningDays,
            ],
            'maxRetries' => 3,
        ];
    }
}
