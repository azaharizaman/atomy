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
        $resolution = (string) $this->input('resolution', '');
        $requiresResolutionData = in_array($resolution, [
            'remap_to_rfq_line',
            'split_line',
            'merge_lines',
            'override_price',
            'override_uom',
        ], true);

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
            'resolution_data' => [$requiresResolutionData ? 'required' : 'nullable', 'array'],
            'resolution_data.rfq_line_id' => ['required_if:resolution,remap_to_rfq_line', 'string'],
            'resolution_data.source_line_ids' => [
                Rule::requiredIf(in_array($resolution, ['split_line', 'merge_lines'], true)),
                'array',
                'min:2',
            ],
            'resolution_data.source_line_ids.*' => ['string'],
            'resolution_data.unit_price' => ['required_if:resolution,override_price', 'numeric', 'min:0'],
            'resolution_data.currency' => ['required_if:resolution,override_price', 'string', 'max:8'],
            'resolution_data.uom' => ['required_if:resolution,override_uom', 'string', 'max:32'],
            'issue_code' => ['nullable', 'string', Rule::in(NormalizationIssueCode::all())],
        ];
    }
}
