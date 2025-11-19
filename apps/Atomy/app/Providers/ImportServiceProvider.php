<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Import\DbImportRepository;
use App\Services\Import\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;
use Nexus\Import\Contracts\ImportRepositoryInterface;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Services\ImportManager;
use Nexus\Import\Services\ImportProcessor;
use Nexus\Import\Core\Engine\DuplicateDetector;
use Nexus\Import\Core\Engine\DataTransformer;
use Nexus\Import\Core\Engine\DataValidator;
use Nexus\Import\Parsers\CsvParser;
use Nexus\Import\Parsers\JsonParser;
use Nexus\Import\Parsers\XmlParser;

/**
 * Import Service Provider
 * 
 * Binds all Import package contracts to their Laravel implementations
 */
final class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(ImportRepositoryInterface::class, DbImportRepository::class);
        
        // Transaction Manager binding
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);

        // Register parsers
        $this->registerParsers();

        // Core services (singletons for performance)
        $this->app->singleton(ImportManager::class);
        $this->app->singleton(ImportProcessor::class);
        $this->app->singleton(DuplicateDetector::class);
        $this->app->singleton(DataTransformer::class);
        $this->app->singleton(DataValidator::class);
    }

    public function boot(): void
    {
        // Publish migrations if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'import-migrations');
        }
    }

    private function registerParsers(): void
    {
        // Register built-in parsers as singletons
        $this->app->singleton(CsvParser::class);
        $this->app->singleton(JsonParser::class);
        $this->app->singleton(XmlParser::class);

        // Note: ExcelParser would be registered here when PHP extensions are available
        // $this->app->singleton(ExcelParser::class);

        // Tag all parsers for easy retrieval
        $this->app->tag([
            CsvParser::class,
            JsonParser::class,
            XmlParser::class,
        ], 'import.parsers');
    }
}
