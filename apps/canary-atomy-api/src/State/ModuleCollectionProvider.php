<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Module as ModuleResource;
use App\Service\ModuleInstaller;
use App\Service\ModuleRegistry;

/**
 * Collection provider for Module resource.
 */
final class ModuleCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ModuleRegistry $moduleRegistry,
        private readonly ModuleInstaller $moduleInstaller
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<ModuleResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $availableModules = $this->moduleRegistry->getAvailableModules();
        $installedModules = $this->moduleInstaller->getInstalledModules();
        $installedMap = [];

        foreach ($installedModules as $installed) {
            $installedMap[$installed->getModuleId()] = $installed;
        }

        $modules = [];

        foreach ($availableModules as $available) {
            $moduleId = basename($available['path']);
            $installed = $installedMap[$moduleId] ?? null;

            $module = new ModuleResource();
            $module->id = $moduleId;
            $module->moduleId = $moduleId;
            $module->name = $available['name'];
            $module->description = $available['description'];
            $module->version = $available['version'];
            $module->isInstalled = $installed !== null;

            if ($installed !== null) {
                $module->installedAt = $installed->getInstalledAt()->format(\DateTimeInterface::ISO8601);
                $module->installedBy = $installed->getInstalledBy();
            }

            $modules[] = $module;
        }

        return $modules;
    }
}
