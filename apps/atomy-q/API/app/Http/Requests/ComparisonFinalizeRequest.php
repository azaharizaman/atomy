<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates final comparison requests against the authenticated tenant RFQ scope.
 *
 * The RFQ existence rule is tenant-qualified when tenant context is present so
 * finalization cannot target another tenant's sourcing event.
 */
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
