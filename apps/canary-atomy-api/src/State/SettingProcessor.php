<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Setting as SettingResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\SettingsCoordinatorInterface;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor for Setting resource.
 *
 * Handles both single update (PATCH) and bulk update (POST).
 */
final class SettingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SettingsCoordinatorInterface $settingsCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param SettingResource|array $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SettingResource|array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($operation->getName() === '_api_/settings/bulk-update_post') {
            return $this->handleBulkUpdate($data, $tenantId);
        }

        return $this->handleSingleUpdate($data, $uriVariables['key'], $tenantId);
    }

    private function handleSingleUpdate(SettingResource $data, string $key, ?string $tenantId): SettingResource
    {
        $request = new SettingUpdateRequest(
            key: $key,
            value: $data->value,
            tenantId: $tenantId
        );

        $result = $this->settingsCoordinator->updateSetting($request);

        if (!$result->isSuccess()) {
            throw new BadRequestHttpException($result->getMessage() ?: 'Update failed');
        }

        return $data;
    }

    private function handleBulkUpdate(array $data, ?string $tenantId): array
    {
        $updates = [];
        foreach ($data as $item) {
            if (isset($item['key'], $item['value'])) {
                $updates[$item['key']] = $item['value'];
            }
        }

        $request = new BulkSettingUpdateRequest(
            settings: $updates,
            tenantId: $tenantId
        );

        $result = $this->settingsCoordinator->bulkUpdateSettings($request);

        if (!$result->isSuccess()) {
            throw new BadRequestHttpException($result->getMessage() ?: 'Bulk update failed');
        }

        return $data;
    }
}
