<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk RFQ lifecycle actions against the supported transition set.
 *
 * Distinct RFQ identifiers prevent duplicate work inside the tenant-scoped bulk
 * close/cancel operation.
 */
final class RfqBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', \Illuminate\Validation\Rule::in(['close', 'cancel'])],
            'rfq_ids' => ['required', 'array', 'min:1'],
            'rfq_ids.*' => ['required', 'string', 'distinct'],
        ];
    }
}
