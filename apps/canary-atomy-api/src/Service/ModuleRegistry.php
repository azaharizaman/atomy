<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Module Registry Service.
 *
 * Scans the orchestrators directory to identify available modules
 * and reads their composer.json to get module metadata.
 */
final class ModuleRegistry
{
    private const ORCHESTRATORS_PATH = '../../orchestrators';

    public function __construct(
        private readonly Filesystem $filesystem
    ) {}

    /**
     * Get list of all available modules from the orchestrators directory.
     *
     * @return array<int, array{name: string, description: string, version: string, path: string}>
     */
    public function getAvailableModules(): array
    {
        $orchestratorsPath = $this->getOrchestratorsPath();
        $modules = [];

        if (!$this->filesystem->exists($orchestratorsPath)) {
            return $modules;
        }

        $directories = array_filter(
            scandir($orchestratorsPath),
            fn(string $entry): bool => 
                $entry !== '.' && $entry !== '..' && is_dir($orchestratorsPath . '/' . $entry)
        );

        foreach ($directories as $directory) {
            $composerJsonPath = $directory . '/composer.json';

            if ($this->filesystem->exists($composerJsonPath)) {
                $metadata = $this->readModuleMetadata($composerJsonPath);

                if ($metadata !== null) {
                    $modules[] = [
                        'name' => $metadata['name'] ?? basename($directory),
                        'description' => $metadata['description'] ?? '',
                        'version' => $metadata['version'] ?? '1.0.0',
                        'path' => $directory,
                    ];
                }
            }
        }

        return $modules;
    }

    /**
     * Get a specific module by its ID (directory name).
     *
     * @return array{name: string, description: string, version: string, path: string}|null
     */
    public function getModule(string $moduleId): ?array
    {
        $orchestratorsPath = $this->getOrchestratorsPath();
        $modulePath = $orchestratorsPath . '/' . $moduleId;
        $composerJsonPath = $modulePath . '/composer.json';

        if (!$this->filesystem->exists($composerJsonPath)) {
            return null;
        }

        $metadata = $this->readModuleMetadata($composerJsonPath);

        if ($metadata === null) {
            return null;
        }

        return [
            'name' => $metadata['name'] ?? $moduleId,
            'description' => $metadata['description'] ?? '',
            'version' => $metadata['version'] ?? '1.0.0',
            'path' => $modulePath,
        ];
    }

    /**
     * Check if a module exists.
     */
    public function moduleExists(string $moduleId): bool
    {
        return $this->getModule($moduleId) !== null;
    }

    /**
     * Get the orchestrators path.
     */
    private function getOrchestratorsPath(): string
    {
        // Get the directory of this file (src/Service/)
        // Then navigate to the project root and to orchestrators
        $reflection = new \ReflectionClass($this);
        $serviceDir = dirname($reflection->getFileName());
        $srcDir = dirname($serviceDir);
        $projectDir = dirname($srcDir);

        return $projectDir . '/' . self::ORCHESTRATORS_PATH;
    }

    /**
     * Read module metadata from composer.json.
     *
     * @return array{name?: string, description?: string, version?: string}|null
     */
    private function readModuleMetadata(string $composerJsonPath): ?array
    {
        try {
            $content = file_get_contents($composerJsonPath);
            if ($content === false) {
                return null;
            }

            $composer = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return [
                'name' => $composer['name'] ?? null,
                'description' => $composer['description'] ?? '',
                'version' => $composer['version'] ?? '1.0.0',
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
