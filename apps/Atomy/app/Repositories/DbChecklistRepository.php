<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ChecklistTemplate;
use Nexus\FieldService\Contracts\ChecklistRepositoryInterface;

final readonly class DbChecklistRepository implements ChecklistRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function findById(string $id): ?ChecklistTemplate
    {
        return ChecklistTemplate::forTenant($this->tenantId)->find($id);
    }

    public function save(array $data): ChecklistTemplate
    {
        if (isset($data['id'])) {
            $template = ChecklistTemplate::forTenant($this->tenantId)->findOrFail($data['id']);
            $template->update($data);
            return $template;
        }

        return ChecklistTemplate::create(array_merge($data, ['tenant_id' => $this->tenantId]));
    }

    public function delete(string $id): void
    {
        $template = ChecklistTemplate::forTenant($this->tenantId)->findOrFail($id);
        $template->delete();
    }

    public function getActiveTemplates(): array
    {
        return ChecklistTemplate::forTenant($this->tenantId)
            ->active()
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }

    public function getAllTemplates(): array
    {
        return ChecklistTemplate::forTenant($this->tenantId)
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }
}
