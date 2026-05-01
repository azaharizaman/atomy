<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NormalizationOverrideReasonCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates buyer-created or buyer-edited normalization source lines.
 *
 * PATCH requests preserve omitted fields while create requests require the
 * source description and reason; `other` reasons must carry an explanatory note.
 */
final class ManualNormalizationSourceLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $partialUpdate = $this->isMethod('PATCH');

        return [
            'source_description' => $partialUpdate ? ['sometimes', 'required', 'string', 'max:2000'] : ['required', 'string', 'max:2000'],
            'source_quantity' => ['nullable', 'numeric', 'min:0'],
            'source_uom' => ['nullable', 'string', 'max:32'],
            'source_unit_price' => ['nullable', 'numeric', 'min:0'],
            'rfq_line_item_id' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
            'reason' => $partialUpdate ? ['sometimes', 'required', 'string', Rule::in(NormalizationOverrideReasonCode::values())] : ['required', 'string', Rule::in(NormalizationOverrideReasonCode::values())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
