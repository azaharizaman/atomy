<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ComparisonFinalizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $tenantId = (string) $this->attributes->get('auth_tenant_id', '');
        $rfqRule = Rule::exists('rfqs', 'id');
        if ($tenantId !== '') {
            $rfqRule = $rfqRule->where('tenant_id', $tenantId);
        }

        return [
            'rfq_id' => ['required', 'string', 'ulid', $rfqRule],
        ];
    }
}
