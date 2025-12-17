<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts\External;

/**
 * External interface for audit logging of privacy operations.
 *
 * This interface must be implemented by the consuming application,
 * typically using the Nexus\AuditLogger package.
 */
interface AuditLoggerInterface
{
    /**
     * Log a privacy-related action.
     *
     * @param string $entityType Type of entity (e.g., 'consent', 'request', 'breach')
     * @param string $entityId Entity identifier
     * @param string $action Action performed (e.g., 'created', 'updated', 'deleted')
     * @param string $description Human-readable description
     * @param array<string, mixed> $metadata Additional context
     */
    public function log(
        string $entityType,
        string $entityId,
        string $action,
        string $description,
        array $metadata = []
    ): void;

    /**
     * Log consent grant event.
     */
    public function logConsentGranted(
        string $dataSubjectId,
        string $purpose,
        string $consentId
    ): void;

    /**
     * Log consent withdrawal event.
     */
    public function logConsentWithdrawn(
        string $dataSubjectId,
        string $purpose,
        string $consentId
    ): void;

    /**
     * Log data subject request submission.
     */
    public function logRequestSubmitted(
        string $requestId,
        string $dataSubjectId,
        string $requestType
    ): void;

    /**
     * Log data subject request completion.
     */
    public function logRequestCompleted(
        string $requestId,
        string $dataSubjectId,
        string $requestType
    ): void;

    /**
     * Log data breach detection.
     */
    public function logBreachDetected(
        string $breachId,
        string $severity,
        int $recordsAffected
    ): void;

    /**
     * Log regulatory notification.
     */
    public function logRegulatoryNotification(
        string $breachId,
        string $regulatoryBody,
        \DateTimeImmutable $notifiedAt
    ): void;

    /**
     * Log data deletion.
     */
    public function logDataDeleted(
        string $dataSubjectId,
        string $reason
    ): void;

    /**
     * Log data export.
     */
    public function logDataExported(
        string $dataSubjectId,
        string $format
    ): void;
}
