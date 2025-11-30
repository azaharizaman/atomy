<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;

/**
 * Represents a report definition that binds a name, layout, and delivery configuration
 * to an underlying Analytics query.
 *
 * Report definitions are templates that can be executed on-demand or scheduled.
 */
interface ReportDefinitionInterface
{
    /**
     * Get the unique identifier for this report definition.
     */
    public function getId(): string;

    /**
     * Get the human-readable report name.
     */
    public function getName(): string;

    /**
     * Get the optional description of the report's purpose.
     */
    public function getDescription(): ?string;

    /**
     * Get the Analytics query ID this report is based on.
     *
     * This query must exist in the Analytics package and the user must have
     * execute permissions on it (SEC-REP-0401).
     */
    public function getQueryId(): string;

    /**
     * Get the user or entity ID that owns this report.
     */
    public function getOwnerId(): string;

    /**
     * Get the output format for this report.
     */
    public function getFormat(): ReportFormat;

    /**
     * Get the schedule configuration, or null if this is an on-demand report.
     */
    public function getSchedule(): ?ReportSchedule;

    /**
     * Get the list of recipients who should receive this report.
     *
     * @return array<\Nexus\Notifier\Contracts\NotifiableInterface>
     */
    public function getRecipients(): array;

    /**
     * Get the default parameters to pass to the Analytics query.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    /**
     * Get the custom template configuration for branding.
     *
     * Contains paths to custom logos, CSS, headers, footers, and version info.
     *
     * @return array{
     *     logo_path?: string,
     *     css_path?: string,
     *     header_html?: string,
     *     footer_html?: string,
     *     template_version?: string
     * }|null
     */
    public function getTemplateConfig(): ?array;

    /**
     * Check if this report is active and should be processed by schedulers.
     */
    public function isActive(): bool;

    /**
     * Get the tenant ID this report belongs to (multi-tenancy).
     */
    public function getTenantId(): ?string;
}
