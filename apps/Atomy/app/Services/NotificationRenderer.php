<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Notifier\Contracts\NotificationRendererInterface;
use Nexus\Notifier\Exceptions\TemplateRenderException;

/**
 * Notification Template Renderer
 *
 * Renders notification templates with variable substitution using Blade-like syntax.
 * Supports: {{ $variable }}, {!! $html !!}, @if, @foreach, etc.
 */
final readonly class NotificationRenderer implements NotificationRendererInterface
{
    /**
     * Render a template with variables
     *
     * @param string $template Template content with placeholders
     * @param array<string, mixed> $variables Variables for substitution
     * @return string Rendered content
     * @throws TemplateRenderException
     */
    public function render(string $template, array $variables): string
    {
        try {
            // Validate template syntax first
            if (!$this->validate($template)) {
                throw TemplateRenderException::invalidSyntax($template);
            }

            $rendered = $template;

            // Replace escaped variables {{ $var }}
            $rendered = preg_replace_callback(
                '/\{\{\s*\$?(\w+)\s*\}\}/',
                function ($matches) use ($variables) {
                    $key = $matches[1];
                    if (!array_key_exists($key, $variables)) {
                        throw TemplateRenderException::missingVariable($key, $matches[0]);
                    }
                    return htmlspecialchars((string) $variables[$key], ENT_QUOTES, 'UTF-8');
                },
                $rendered
            );

            // Replace unescaped variables {!! $var !!}
            $rendered = preg_replace_callback(
                '/\{!!\s*\$?(\w+)\s*!!\}/',
                function ($matches) use ($variables) {
                    $key = $matches[1];
                    if (!array_key_exists($key, $variables)) {
                        throw TemplateRenderException::missingVariable($key, $matches[0]);
                    }
                    return (string) $variables[$key];
                },
                $rendered
            );

            // Handle @if statements
            $rendered = $this->renderConditionals($rendered, $variables);

            // Handle @foreach statements
            $rendered = $this->renderLoops($rendered, $variables);

            return $rendered;
        } catch (TemplateRenderException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw TemplateRenderException::renderingFailed($e->getMessage(), $e);
        }
    }

    /**
     * Render email template with layout
     *
     * @param string $subject Email subject with placeholders
     * @param string $body Email body with placeholders
     * @param array<string, mixed> $variables Variables for substitution
     * @return array{subject: string, body: string} Rendered email content
     */
    public function renderEmail(string $subject, string $body, array $variables): array
    {
        return [
            'subject' => $this->render($subject, $variables),
            'body' => $this->render($body, $variables),
        ];
    }

    /**
     * Check if a template string is valid
     */
    public function validate(string $template): bool
    {
        // Check for balanced braces
        $openBraces = substr_count($template, '{{');
        $closeBraces = substr_count($template, '}}');
        
        if ($openBraces !== $closeBraces) {
            return false;
        }

        $openUnescaped = substr_count($template, '{!!');
        $closeUnescaped = substr_count($template, '!!}');
        
        if ($openUnescaped !== $closeUnescaped) {
            return false;
        }

        // Check for balanced @if/@endif
        $ifCount = substr_count($template, '@if');
        $endifCount = substr_count($template, '@endif');
        
        if ($ifCount !== $endifCount) {
            return false;
        }

        // Check for balanced @foreach/@endforeach
        $foreachCount = substr_count($template, '@foreach');
        $endforeachCount = substr_count($template, '@endforeach');
        
        if ($foreachCount !== $endforeachCount) {
            return false;
        }

        return true;
    }

    /**
     * Extract variable names from a template
     *
     * @return array<string> Variable names
     */
    public function extractVariables(string $template): array
    {
        $variables = [];

        // Extract from {{ $var }}
        preg_match_all('/\{\{\s*\$?(\w+)\s*\}\}/', $template, $matches);
        $variables = array_merge($variables, $matches[1]);

        // Extract from {!! $var !!}
        preg_match_all('/\{!!\s*\$?(\w+)\s*!!\}/', $template, $matches);
        $variables = array_merge($variables, $matches[1]);

        // Extract from @if($var)
        preg_match_all('/@if\s*\(\s*\$?(\w+)\s*\)/', $template, $matches);
        $variables = array_merge($variables, $matches[1]);

        // Extract from @foreach($items as $item)
        preg_match_all('/@foreach\s*\(\s*\$?(\w+)\s+as\s+\$?\w+\s*\)/', $template, $matches);
        $variables = array_merge($variables, $matches[1]);

        return array_unique($variables);
    }

    /**
     * Render conditional statements (@if/@else/@endif)
     *
     * @param array<string, mixed> $variables
     */
    private function renderConditionals(string $template, array $variables): string
    {
        $pattern = '/@if\s*\(\s*\$?(\w+)\s*\)(.*?)(?:@else(.*?))?@endif/s';
        
        return preg_replace_callback(
            $pattern,
            function ($matches) use ($variables) {
                $variable = $matches[1];
                $trueContent = $matches[2];
                $falseContent = $matches[3] ?? '';

                $value = $variables[$variable] ?? false;

                return $value ? $trueContent : $falseContent;
            },
            $template
        );
    }

    /**
     * Render loop statements (@foreach/@endforeach)
     *
     * @param array<string, mixed> $variables
     */
    private function renderLoops(string $template, array $variables): string
    {
        $pattern = '/@foreach\s*\(\s*\$?(\w+)\s+as\s+\$?(\w+)\s*\)(.*?)@endforeach/s';
        
        return preg_replace_callback(
            $pattern,
            function ($matches) use ($variables) {
                $arrayVar = $matches[1];
                $itemVar = $matches[2];
                $loopContent = $matches[3];

                $items = $variables[$arrayVar] ?? [];
                if (!is_array($items)) {
                    return '';
                }

                $result = '';
                foreach ($items as $item) {
                    // Create temporary context with loop variable
                    $loopVars = array_merge($variables, [$itemVar => $item]);
                    
                    // Render loop content with item variable
                    $itemContent = preg_replace_callback(
                        '/\{\{\s*\$?' . $itemVar . '\.(\w+)\s*\}\}/',
                        function ($m) use ($item) {
                            $key = $m[1];
                            if (is_array($item) && isset($item[$key])) {
                                return htmlspecialchars((string) $item[$key], ENT_QUOTES, 'UTF-8');
                            }
                            if (is_object($item) && isset($item->$key)) {
                                return htmlspecialchars((string) $item->$key, ENT_QUOTES, 'UTF-8');
                            }
                            return '';
                        },
                        $loopContent
                    );

                    $result .= $itemContent;
                }

                return $result;
            },
            $template
        );
    }
}
