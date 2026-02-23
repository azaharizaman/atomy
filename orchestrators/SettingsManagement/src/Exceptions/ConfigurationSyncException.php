<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Exceptions;

/**
 * Base exception for configuration sync operations.
 */
class ConfigurationSyncException extends SettingsManagementException
{
}

/**
 * Exception thrown when configuration import fails.
 */
class ConfigurationImportException extends ConfigurationSyncException
{
    public static function importFailed(string $reason, array $errors = []): self
    {
        return new self(
            message: "Configuration import failed: {$reason}",
            context: ['reason' => $reason, 'errors' => $errors]
        );
    }
}

/**
 * Exception thrown when configuration export fails.
 */
class ConfigurationExportException extends ConfigurationSyncException
{
    public static function exportFailed(string $reason): self
    {
        return new self(
            message: "Configuration export failed: {$reason}",
            context: ['reason' => $reason]
        );
    }
}
