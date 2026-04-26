<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'source_description' => ['required', 'string', 'max:2000'],
            'source_quantity' => ['nullable', 'numeric', 'min:0'],
            'source_uom' => ['nullable', 'string', 'max:32'],
            'source_unit_price' => ['nullable', 'numeric', 'min:0'],
            'rfq_line_item_id' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
            'reason' => ['required', 'string', Rule::in([
                'supplier_document_mismatch',
                'rfq_mapping_incorrect',
                'quantity_or_uom_correction',
                'price_correction',
                'manual_entry_required',
                'other',
            ])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
