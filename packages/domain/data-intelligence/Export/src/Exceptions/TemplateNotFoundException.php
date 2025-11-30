<?php

declare(strict_types=1);

namespace Nexus\Export\Exceptions;

/**
 * Template not found exception
 * 
 * Thrown when requested template ID does not exist
 */
class TemplateNotFoundException extends TemplateException
{
    public static function forId(string $templateId): self
    {
        return new self("Template not found: {$templateId}");
    }
}
