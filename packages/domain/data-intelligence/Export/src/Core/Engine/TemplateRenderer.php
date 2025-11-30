<?php

declare(strict_types=1);

namespace Nexus\Export\Core\Engine;

use Nexus\Export\Contracts\TemplateEngineInterface;
use Nexus\Export\Exceptions\TemplateException;
use Nexus\Export\Exceptions\TemplateNotFoundException;

/**
 * Template renderer implementation
 * 
 * Provides basic variable substitution, conditionals, and loops.
 * Uses Mustache-like syntax: {{ variable }}, {{#if}}, {{#each}}
 * 
 * For advanced layouts (PDF headers/footers, HTML styling), applications
 * should provide specialized TemplateEngine implementations.
 */
final class TemplateRenderer implements TemplateEngineInterface
{
    /**
     * Template storage
     * 
     * @var array<string, string>
     */
    private array $templates = [];

    /**
     * Register template content
     */
    public function registerTemplate(string $id, string $content): void
    {
        $this->templates[$id] = $content;
    }

    /**
     * Render template with data context
     * 
     * @param array<string, mixed> $context Data for variable substitution
     * @throws TemplateNotFoundException
     * @throws TemplateException
     */
    public function render(string $templateId, array $context): string
    {
        $template = $this->getTemplate($templateId);
        
        try {
            return $this->processTemplate($template, $context);
        } catch (\Throwable $e) {
            throw new TemplateException(
                "Template rendering failed for '{$templateId}': {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Validate template syntax
     * 
     * @return bool True if valid, false otherwise
     */
    public function validate(string $template): bool
    {
        try {
            // Check for unclosed tags
            $openTags = preg_match_all('/\{\{#(if|each)\s+/', $template);
            $closeTags = preg_match_all('/\{\{\/(if|each)\}\}/', $template);
            
            if ($openTags !== $closeTags) {
                return false;
            }

            // Check for invalid variable syntax
            if (preg_match('/\{\{[^}]*[^}\w\s\.\-_#\/][^}]*\}\}/', $template)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Extract variables from template
     * 
     * @return string[] Variable names found in template
     */
    public function extractVariables(string $template): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9\.\-_]+)\s*\}\}/', $template, $matches);
        
        return array_unique($matches[1] ?? []);
    }

    /**
     * Get template content
     * 
     * @throws TemplateNotFoundException
     */
    public function getTemplate(string $templateId): string
    {
        if (!isset($this->templates[$templateId])) {
            throw TemplateNotFoundException::forId($templateId);
        }

        return $this->templates[$templateId];
    }

    /**
     * Process template with context recursively
     * 
     * @param array<string, mixed> $context
     */
    private function processTemplate(string $template, array $context): string
    {
        // Process conditionals: {{#if variable}} ... {{/if}}
        $template = $this->processConditionals($template, $context);
        
        // Process loops: {{#each items}} ... {{/each}}
        $template = $this->processLoops($template, $context);
        
        // Process simple variables: {{ variable }}
        $template = $this->processVariables($template, $context);

        return $template;
    }

    /**
     * Process conditional blocks
     * 
     * @param array<string, mixed> $context
     */
    private function processConditionals(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{#if\s+([a-zA-Z0-9\.\-_]+)\s*\}\}(.*?)\{\{\/if\}\}/s',
            function (array $matches) use ($context): string {
                $variable = $matches[1];
                $content = $matches[2];
                
                $value = $this->resolveVariable($variable, $context);
                
                return $this->isTruthy($value) ? $content : '';
            },
            $template
        );
    }

    /**
     * Process loop blocks
     * 
     * @param array<string, mixed> $context
     */
    private function processLoops(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{#each\s+([a-zA-Z0-9\.\-_]+)\s*\}\}(.*?)\{\{\/each\}\}/s',
            function (array $matches) use ($context): string {
                $variable = $matches[1];
                $content = $matches[2];
                
                $items = $this->resolveVariable($variable, $context);
                
                if (!is_array($items)) {
                    return '';
                }

                $output = '';
                foreach ($items as $index => $item) {
                    $itemContext = is_array($item) ? $item : ['item' => $item, 'index' => $index];
                    $output .= $this->processTemplate($content, $itemContext);
                }

                return $output;
            },
            $template
        );
    }

    /**
     * Process simple variable substitutions
     * 
     * @param array<string, mixed> $context
     */
    private function processVariables(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9\.\-_]+)\s*\}\}/',
            function (array $matches) use ($context): string {
                $variable = $matches[1];
                $value = $this->resolveVariable($variable, $context);
                
                return $this->formatValue($value);
            },
            $template
        );
    }

    /**
     * Resolve variable from context (supports dot notation)
     * 
     * @param array<string, mixed> $context
     */
    private function resolveVariable(string $variable, array $context): mixed
    {
        $parts = explode('.', $variable);
        $value = $context;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check if value is truthy
     */
    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '' || $value === 0 || $value === '0') {
            return false;
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
    }

    /**
     * Format value for output
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
