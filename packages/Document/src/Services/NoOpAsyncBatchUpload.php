<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\AsyncBatchUploadInterface;
use Symfony\Component\Uid\Ulid;

/**
 * No-op async batch processor.
 */
final class NoOpAsyncBatchUpload implements AsyncBatchUploadInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatchBatch(array $files, string $ownerId): string
    {
        return (string) new Ulid();
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchStatus(string $jobId): array
    {
        return [
            'job_id' => $jobId,
            'status' => 'COMPLETED',
            'progress' => 100,
        ];
    }
}
