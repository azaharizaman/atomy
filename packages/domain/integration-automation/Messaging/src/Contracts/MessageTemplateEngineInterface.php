<?php

declare(strict_types=1);

namespace Nexus\Messaging\Contracts;

/**
 * Template engine contract for rendering message bodies
 * 
 * L3.3: Application layer implements using Twig, Blade, or custom template engine
 * 
 * @package Nexus\Messaging
 */
interface MessageTemplateEngineInterface
{
    /**
     * Render message body from template
     * 
     * @param string $templateId Template identifier
     * @param array<string, mixed> $context Data for template
     * @return string Rendered message body
     * @throws \RuntimeException If template not found or rendering fails
     */
    public function render(string $templateId, array $context): string;

    /**
     * Check if template exists
     * 
     * @param string $templateId
     * @return bool
     */
    public function templateExists(string $templateId): bool;

    /**
     * Get template subject (for email templates)
     * 
     * @param string $templateId
     * @param array<string, mixed> $context
     * @return string|null
     */
    public function renderSubject(string $templateId, array $context): ?string;
}
