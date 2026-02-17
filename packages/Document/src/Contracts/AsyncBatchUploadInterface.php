<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Interface for asynchronous document batch upload processing.
 *
 * Allows offloading large batch uploads to background queues.
 */
interface AsyncBatchUploadInterface
{
    /**
     * Dispatch a batch of files for background processing.
     *
     * @param array<array{stream: resource, metadata: array}> $files
     * @param string $ownerId
     * @return string Batch job identifier
     */
    public function dispatchBatch(array $files, string $ownerId): string;

    /**
     * Get the status of a batch job.
     *
     * @param string $jobId
     * @return array{status: string, progress: float, total: int, completed: int, failed: int}
     */
    public function getBatchStatus(string $jobId): array;
}
