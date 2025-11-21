<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

/**
 * Defines a custom report template with branding assets and layout configuration.
 *
 * Advanced feature (Option C) for enterprise users requiring brand-aligned reports.
 * Templates support versioning for audit trail and rollback capability.
 */
interface ReportTemplateInterface
{
    /**
     * Get the unique identifier for this template.
     */
    public function getTemplateId(): string;

    /**
     * Get the template name.
     */
    public function getName(): string;

    /**
     * Get the template version (for audit trail and rollback).
     */
    public function getVersion(): string;

    /**
     * Get the paths to template assets (logos, CSS files).
     *
     * All paths should be storage:// URIs referencing Nexus\Storage.
     *
     * @return array{
     *     logo_path?: string,
     *     css_path?: string,
     *     header_image_path?: string,
     *     footer_image_path?: string
     * }
     */
    public function getAssetPaths(): array;

    /**
     * Get the layout configuration for the template.
     *
     * Contains HTML snippets for headers, footers, and styling overrides.
     *
     * @return array{
     *     header_html?: string,
     *     footer_html?: string,
     *     page_size?: string,
     *     orientation?: string,
     *     margins?: array{top: int, right: int, bottom: int, left: int}
     * }
     */
    public function getLayoutConfig(): array;

    /**
     * Get the timestamp when this template version was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Check if this template is active and available for use.
     */
    public function isActive(): bool;
}
