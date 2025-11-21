<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Export\Contracts\DefinitionValidatorInterface;
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\Contracts\TemplateEngineInterface;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Formatters\XmlFormatter;
use Nexus\Export\Core\Formatters\TxtFormatter;
use Nexus\Export\Services\ExportManager;
use Nexus\Export\ValueObjects\ExportFormat;

/**
 * Export Service Provider
 *
 * Binds Export package contracts to implementations.
 */
class ExportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind validator
        $this->app->singleton(DefinitionValidatorInterface::class, DefinitionValidator::class);

        // Register formatters (lazy instantiation)
        $this->app->singleton(CsvFormatter::class);
        $this->app->singleton(JsonFormatter::class);
        $this->app->singleton(XmlFormatter::class);
        $this->app->singleton(TxtFormatter::class);

        // Register export manager
        $this->app->singleton(ExportManagerInterface::class, function ($app) {
            $formatters = [];
            $formatters[ExportFormat::CSV->value] = $app->make(CsvFormatter::class);
            $formatters[ExportFormat::JSON->value] = $app->make(JsonFormatter::class);
            $formatters[ExportFormat::XML->value] = $app->make(XmlFormatter::class);
            $formatters[ExportFormat::TXT->value] = $app->make(TxtFormatter::class);

            return new ExportManager(
                formatters: $formatters,
                validator: $app->make(DefinitionValidatorInterface::class),
                templateEngine: null, // Optional: bind if needed
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
