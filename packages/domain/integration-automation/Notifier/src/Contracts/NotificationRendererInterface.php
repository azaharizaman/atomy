<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

/**
 * Notification Renderer Interface
 *
 * Handles template rendering with variable substitution.
 */
interface NotificationRendererInterface
{
    /**
     * Render a template with variables
     *
     * @param string $template Template content with placeholders
     * @param array<string, mixed> $variables Variables for substitution
     * @return string Rendered content
     * @throws \Nexus\Notifier\Exceptions\TemplateRenderException
     */
    public function render(string $template, array $variables): string;

    /**
     * Render email template with layout
     *
     * @param string $subject Email subject with placeholders
     * @param string $body Email body with placeholders
     * @param array<string, mixed> $variables Variables for substitution
     * @return array{subject: string, body: string} Rendered email content
     */
    public function renderEmail(string $subject, string $body, array $variables): array;

    /**
     * Check if a template string is valid
     *
     * @param string $template
     * @return bool True if valid
     */
    public function validate(string $template): bool;

    /**
     * Extract variable names from a template
     *
     * @param string $template
     * @return array<string> Variable names
     */
    public function extractVariables(string $template): array;
}
