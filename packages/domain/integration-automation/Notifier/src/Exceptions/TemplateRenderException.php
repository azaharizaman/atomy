<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

use Throwable;

/**
 * Template Render Exception
 *
 * Thrown when template rendering fails.
 */
final class TemplateRenderException extends NotificationException
{
    public static function invalidSyntax(string $template, ?Throwable $previous = null): self
    {
        return new self("Invalid template syntax: {$template}", 0, $previous);
    }

    public static function missingVariable(string $variable, string $template): self
    {
        return new self("Required variable '{$variable}' missing from template: {$template}");
    }

    public static function renderingFailed(string $reason, ?Throwable $previous = null): self
    {
        return new self("Template rendering failed: {$reason}", 0, $previous);
    }
}
