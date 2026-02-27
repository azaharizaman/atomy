<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Module as ModuleResource;
use App\Service\ModuleInstaller;
use App\Service\ModuleRegistry;

/**
 * Item provider for Module resource.
 */
final class ModuleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly ModuleRegistry $moduleRegistry,
        private readonly ModuleInstaller $moduleInstaller
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ModuleResource
    {
        $moduleId = $uriVariables['moduleId'] ?? null;

        if ($moduleId === null) {
            return null;
        }

        $available = $this->moduleRegistry->getModule($moduleId);

        if ($available === null) {
            return null;
        }

        $installed = $this->moduleInstaller->findInstalledModule($moduleId);

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

        return $module;
    }
}
