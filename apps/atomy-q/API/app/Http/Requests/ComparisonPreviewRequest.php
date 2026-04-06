<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ComparisonPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->tenantId() !== '';
    }

    /**
     * @return array<string, array<int, object|string>>
     */
    public function rules(): array
    {
        $tenantId = $this->tenantId();

        return [
            'rfq_id' => [
                'required',
                'string',
                'ulid',
                Rule::exists('rfqs', 'id')->where('tenant_id', $tenantId === '' ? '0' : $tenantId),
            ],
        ];
    }

    private function tenantId(): string
    {
        return trim((string) $this->attributes->get('auth_tenant_id', ''));
    }
}
