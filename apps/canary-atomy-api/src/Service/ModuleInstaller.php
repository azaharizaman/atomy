<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InstalledModule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Module Installer Service.
 *
 * Handles installation lifecycle of modules including:
 * - Marking modules as "installed" in the database
 * - Triggering setup procedures
 * - Creating default settings
 * - Supporting uninstallation
 */
final class ModuleInstaller
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ModuleRegistry $moduleRegistry
    ) {}

    /**
     * Install a module for a specific tenant.
     *
     * @throws \InvalidArgumentException if module doesn't exist
     * @throws \RuntimeException if module is already installed
     */
    public function install(string $moduleId, ?string $installedBy = null): InstalledModule
    {
        // Verify module exists in the orchestrators
        $module = $this->moduleRegistry->getModule($moduleId);
        if ($module === null) {
            throw new \InvalidArgumentException(sprintf('Module "%s" not found', $moduleId));
        }

        // Check if already installed
        $existing = $this->findInstalledModule($moduleId);
        if ($existing !== null) {
            throw new \RuntimeException(sprintf('Module "%s" is already installed', $moduleId));
        }

        // Create the installed module entity
        $installedModule = new InstalledModule(
            moduleId: $moduleId,
            installedBy: $installedBy ?? 'system',
            metadata: [
                'name' => $module['name'],
                'description' => $module['description'],
                'version' => $module['version'],
                'installed_version' => $module['version'],
            ]
        );

        $this->entityManager->persist($installedModule);
        $this->entityManager->flush();

        // Trigger post-install setup
        $this->postInstall($installedModule);

        return $installedModule;
    }

    /**
     * Uninstall a module.
     *
     * @throws \InvalidArgumentException if module is not installed
     */
    public function uninstall(string $moduleId): void
    {
        $installedModule = $this->findInstalledModule($moduleId);

        if ($installedModule === null) {
            throw new \InvalidArgumentException(sprintf('Module "%s" is not installed', $moduleId));
        }

        // Trigger pre-uninstall cleanup
        $this->preUninstall($installedModule);

        $this->entityManager->remove($installedModule);
        $this->entityManager->flush();
    }

    /**
     * Check if a module is installed.
     */
    public function isInstalled(string $moduleId): bool
    {
        return $this->findInstalledModule($moduleId) !== null;
    }

    /**
     * Get all installed modules.
     *
     * @return array<int, InstalledModule>
     */
    public function getInstalledModules(): array
    {
        $repository = $this->entityManager->getRepository(InstalledModule::class);

        return $repository->findAll();
    }

    /**
     * Find an installed module by module ID.
     */
    public function findInstalledModule(string $moduleId): ?InstalledModule
    {
        $repository = $this->entityManager->getRepository(InstalledModule::class);

        return $repository->findOneBy(['moduleId' => $moduleId]);
    }

    /**
     * Get installed module with metadata merged from registry.
     *
     * @return array<int, array{id: string, moduleId: string, installedAt: \DateTimeInterface, installedBy: string, metadata: array, available: array}>
     */
    public function getInstalledModulesWithAvailability(): array
    {
        $installedModules = $this->getInstalledModules();
        $availableModules = $this->moduleRegistry->getAvailableModules();
        $availableMap = [];

        foreach ($availableModules as $module) {
            $moduleId = basename($module['path']);
            $availableMap[$moduleId] = $module;
        }

        $result = [];

        foreach ($installedModules as $installed) {
            $moduleId = $installed->getModuleId();
            $available = $availableMap[$moduleId] ?? null;

            $result[] = [
                'id' => $installed->getId(),
                'moduleId' => $moduleId,
                'installedAt' => $installed->getInstalledAt(),
                'installedBy' => $installed->getInstalledBy(),
                'metadata' => $installed->getMetadata(),
                'available' => $available,
                'isInstalled' => true,
            ];
        }

        // Add available but not installed modules
        foreach ($availableModules as $module) {
            $moduleId = basename($module['path']);
            if (!isset($availableMap[$moduleId])) {
                $result[] = [
                    'id' => '',
                    'moduleId' => $moduleId,
                    'installedAt' => null,
                    'installedBy' => '',
                    'metadata' => [],
                    'available' => $module,
                    'isInstalled' => false,
                ];
            }
        }

        return $result;
    }

    /**
     * Post-install hook for module setup.
     */
    private function postInstall(InstalledModule $module): void
    {
        // This can be extended to:
        // - Create default settings
        // - Initialize database tables
        // - Set up default data
        // - Trigger events

        // For now, this is a placeholder for the installation lifecycle
    }

    /**
     * Pre-uninstall hook for cleanup.
     */
    private function preUninstall(InstalledModule $module): void
    {
        // This can be extended to:
        // - Clean up module data
        // - Remove settings
        // - Trigger events

        // For now, this is a placeholder for the uninstallation lifecycle
    }
}
