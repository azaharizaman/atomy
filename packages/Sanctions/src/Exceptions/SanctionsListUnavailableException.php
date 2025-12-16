<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Exceptions;

use Nexus\Sanctions\Enums\SanctionsList;

/**
 * Exception thrown when a sanctions list is temporarily unavailable.
 * 
 * This can occur due to:
 * - List provider API is down or under maintenance
 * - List data is being updated (refresh in progress)
 * - Rate limit exceeded on list provider
 * - Authentication/authorization failure with list provider
 * - List subscription expired
 * 
 * Contains information about the unavailable list and retry guidance.
 * 
 * @package Nexus\Sanctions\Exceptions
 */
final class SanctionsListUnavailableException extends SanctionsException
{
    private ?SanctionsList $list = null;
    private ?int $retryAfterSeconds = null;
    private ?string $listUrl = null;

    /**
     * Create exception for API maintenance.
     *
     * @param SanctionsList $list Sanctions list
     * @param \DateTimeImmutable $expectedAvailable Expected availability time
     * @return self
     */
    public static function underMaintenance(
        SanctionsList $list,
        \DateTimeImmutable $expectedAvailable
    ): self {
        $exception = new self(
            "Sanctions list {$list->getName()} is under maintenance. " . 
            "Expected to be available at " . $expectedAvailable->format('Y-m-d H:i:s T'),
            503
        );
        
        $exception->list = $list;
        $exception->retryAfterSeconds = max(
            0,
            $expectedAvailable->getTimestamp() - time()
        );
        
        return $exception;
    }

    /**
     * Create exception for rate limit exceeded.
     *
     * @param SanctionsList $list Sanctions list
     * @param int $retryAfterSeconds Seconds to wait before retry
     * @param int $requestsPerHour Rate limit
     * @return self
     */
    public static function rateLimitExceeded(
        SanctionsList $list,
        int $retryAfterSeconds,
        int $requestsPerHour
    ): self {
        $exception = new self(
            "Rate limit exceeded for {$list->getName()} " . 
            "({$requestsPerHour} requests/hour). Retry after {$retryAfterSeconds}s",
            429
        );
        
        $exception->list = $list;
        $exception->retryAfterSeconds = $retryAfterSeconds;
        
        return $exception;
    }

    /**
     * Create exception for authentication failure.
     *
     * @param SanctionsList $list Sanctions list
     * @param string $reason Authentication failure reason
     * @return self
     */
    public static function authenticationFailed(
        SanctionsList $list,
        string $reason
    ): self {
        $exception = new self(
            "Authentication failed for {$list->getName()}: {$reason}",
            401
        );
        
        $exception->list = $list;
        $exception->retryAfterSeconds = null; // Requires configuration fix
        
        return $exception;
    }

    /**
     * Create exception for expired subscription.
     *
     * @param SanctionsList $list Sanctions list
     * @param \DateTimeImmutable $expiryDate Subscription expiry date
     * @param string|null $renewalUrl URL to renew subscription
     * @return self
     */
    public static function subscriptionExpired(
        SanctionsList $list,
        \DateTimeImmutable $expiryDate,
        ?string $renewalUrl = null
    ): self {
        $message = "Subscription expired for {$list->getName()} on " . 
                   $expiryDate->format('Y-m-d');
        
        if ($renewalUrl !== null) {
            $message .= ". Renew at: {$renewalUrl}";
        }
        
        $exception = new self($message, 403);
        
        $exception->list = $list;
        $exception->listUrl = $renewalUrl;
        $exception->retryAfterSeconds = null; // Requires renewal
        
        return $exception;
    }

    /**
     * Create exception for data refresh in progress.
     *
     * @param SanctionsList $list Sanctions list
     * @param int $estimatedCompletionSeconds Estimated time to completion
     * @return self
     */
    public static function refreshInProgress(
        SanctionsList $list,
        int $estimatedCompletionSeconds
    ): self {
        $exception = new self(
            "Data refresh in progress for {$list->getName()}. " . 
            "Estimated completion in {$estimatedCompletionSeconds}s",
            503
        );
        
        $exception->list = $list;
        $exception->retryAfterSeconds = $estimatedCompletionSeconds;
        
        return $exception;
    }

    /**
     * Create exception for provider service down.
     *
     * @param SanctionsList $list Sanctions list
     * @param string $providerName Provider name
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function providerDown(
        SanctionsList $list,
        string $providerName,
        ?\Throwable $previous = null
    ): self {
        $exception = new self(
            "List provider '{$providerName}' for {$list->getName()} is currently down",
            503,
            $previous
        );
        
        $exception->list = $list;
        $exception->retryAfterSeconds = 300; // Default 5 minutes
        
        return $exception;
    }

    /**
     * Create exception for multiple lists unavailable.
     *
     * @param array<SanctionsList> $lists Unavailable lists
     * @param string $reason Reason for unavailability
     * @return self
     */
    public static function multipleLists(
        array $lists,
        string $reason
    ): self {
        $listNames = array_map(fn($list) => $list->getName(), $lists);
        
        $exception = new self(
            count($lists) . " sanctions lists are unavailable (" . 
            implode(', ', $listNames) . "): {$reason}",
            503
        );
        
        $exception->retryAfterSeconds = 60; // Default 1 minute
        
        return $exception;
    }

    /**
     * Get the unavailable sanctions list.
     *
     * @return SanctionsList|null
     */
    public function getList(): ?SanctionsList
    {
        return $this->list;
    }

    /**
     * Get seconds to wait before retrying.
     *
     * @return int|null Null means manual intervention required
     */
    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    /**
     * Get URL for list information or renewal.
     *
     * @return string|null
     */
    public function getListUrl(): ?string
    {
        return $this->listUrl;
    }

    /**
     * Check if automatic retry is possible.
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->retryAfterSeconds !== null;
    }

    /**
     * Check if manual intervention is required.
     *
     * @return bool
     */
    public function requiresManualIntervention(): bool
    {
        return !$this->canRetry();
    }

    /**
     * Get retry timestamp if available.
     *
     * @return \DateTimeImmutable|null
     */
    public function getRetryAfterTimestamp(): ?\DateTimeImmutable
    {
        if ($this->retryAfterSeconds === null) {
            return null;
        }
        
        return (new \DateTimeImmutable())->modify("+{$this->retryAfterSeconds} seconds");
    }
}
