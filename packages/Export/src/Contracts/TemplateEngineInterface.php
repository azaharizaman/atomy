<?php

declare(strict_types=1);

namespace Nexus\Export\Contracts;

/**
 * Template engine contract
 * 
 * Renders templates with variable substitution, conditionals, and loops.
 * Extends Nexus\Notifier template rendering pattern.
 */
interface TemplateEngineInterface
{
    /**
     * Render template with data
     * 
     * Supports:
     * - Variable substitution: {{ $variable }}
     * - Escaped HTML: {!! $html !!}
     * - Conditionals: @if($condition) ... @endif
     * - Loops: @foreach($items as $item) ... @endforeach
     * 
     * @param string $template Template content or template ID
     * @param array<string, mixed> $data Template variables
     * @return string Rendered output
     * @throws \Nexus\Export\Exceptions\TemplateException
     */
    public function render(string $template, array $data): string;

    /**
     * Validate template syntax
     * 
     * @param string $template Template content to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $template): bool;

    /**
     * Extract variable names from template
     * 
     * @param string $template Template content
     * @return array<string> List of variable names
     */
    public function extractVariables(string $template): array;

    /**
     * Get template by ID from storage
     * 
     * @param string $templateId Template identifier
     * @return string Template content
     * @throws \Nexus\Export\Exceptions\TemplateNotFoundException
     */
    public function getTemplate(string $templateId): string;
}
