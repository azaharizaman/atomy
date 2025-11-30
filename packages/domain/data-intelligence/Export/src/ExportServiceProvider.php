<?php

declare(strict_types=1);

namespace Nexus\Export;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nexus\Export\Contracts\DefinitionValidatorInterface;
use Nexus\Export\Contracts\TemplateEngineInterface;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\Core\Engine\TemplateRenderer;

/**
 * Export package service provider
 * 
 * Registers default implementations for core services.
 * Applications (Atomy) should override these bindings with
 * their own implementations as needed.
 */
final class ExportServiceProvider extends BaseServiceProvider
{
    /**
     * Register package services
     */
    public function register(): void
    {
        // Register definition validator
        $this->app->singleton(DefinitionValidatorInterface::class, DefinitionValidator::class);

        // Register template engine
        $this->app->singleton(TemplateEngineInterface::class, TemplateRenderer::class);
    }

    /**
     * Bootstrap package services
     */
    public function boot(): void
    {
        // Package bootstrapping if needed
    }
}
