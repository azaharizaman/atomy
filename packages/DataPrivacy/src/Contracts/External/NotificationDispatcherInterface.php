<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts\External;

/**
 * External interface for sending privacy-related notifications.
 *
 * This interface must be implemented by the consuming application,
 * typically using the Nexus\Notifier package.
 */
interface NotificationDispatcherInterface
{
    /**
     * Notify data subject about request status change.
     */
    public function notifyRequestStatusChange(
        string $dataSubjectId,
        string $requestId,
        string $requestType,
        string $newStatus,
        ?string $message = null
    ): void;

    /**
     * Notify data subject about a data breach affecting them.
     */
    public function notifyDataBreach(
        string $dataSubjectId,
        string $breachId,
        string $breachDescription,
        array $affectedDataCategories,
        string $recommendedActions
    ): void;

    /**
     * Notify data subject about consent expiration.
     */
    public function notifyConsentExpiring(
        string $dataSubjectId,
        string $consentId,
        string $purpose,
        \DateTimeImmutable $expiresAt
    ): void;

    /**
     * Notify data subject about successful data export.
     */
    public function notifyDataExportReady(
        string $dataSubjectId,
        string $requestId,
        string $downloadUrl,
        \DateTimeImmutable $expiresAt
    ): void;

    /**
     * Notify data subject about data deletion completion.
     */
    public function notifyDataDeleted(
        string $dataSubjectId,
        string $requestId,
        array $deletedCategories
    ): void;

    /**
     * Notify internal team about new data subject request.
     */
    public function notifyTeamNewRequest(
        string $requestId,
        string $requestType,
        \DateTimeImmutable $deadline
    ): void;

    /**
     * Notify internal team about approaching request deadline.
     */
    public function notifyTeamDeadlineApproaching(
        string $requestId,
        string $requestType,
        int $daysRemaining
    ): void;

    /**
     * Notify internal team about data breach.
     */
    public function notifyTeamBreach(
        string $breachId,
        string $severity,
        int $recordsAffected,
        bool $requiresRegulatoryNotification
    ): void;

    /**
     * Notify regulatory authority about a data breach.
     *
     * @return string Reference number from regulatory authority
     */
    public function notifyRegulatoryAuthority(
        string $breachId,
        string $breachDetails,
        int $recordsAffected,
        array $affectedDataCategories,
        string $mitigationMeasures
    ): string;
}
