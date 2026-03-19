<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\QuoteIntake\NormalizationIssueCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NormalizationResolveConflictRequest extends FormRequest
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
            'resolution' => [
                'required',
                'string',
                Rule::in([
                    'accept_extracted_value',
                    'remap_to_rfq_line',
                    'split_line',
                    'merge_lines',
                    'mark_not_quoted',
                    'override_price',
                    'override_uom',
                ]),
            ],
            'resolution_data' => ['nullable', 'array'],
            'issue_code' => ['nullable', 'string', Rule::in(NormalizationIssueCode::all())],
        ];
    }
}
