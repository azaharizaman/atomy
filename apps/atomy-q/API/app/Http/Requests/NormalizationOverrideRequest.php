<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class NormalizationOverrideRequest extends FormRequest
{
    private const REASON_CODES = [
        'supplier_document_mismatch',
        'rfq_mapping_incorrect',
        'quantity_or_uom_correction',
        'price_correction',
        'manual_entry_required',
        'other',
    ];

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
            'override_data' => ['required', 'array', 'min:1'],
            'override_data.rfq_line_item_id' => ['sometimes', 'string'],
            'override_data.quantity' => ['sometimes', 'numeric', 'min:0'],
            'override_data.uom' => ['sometimes', 'string', 'max:32'],
            'override_data.unit_price' => ['sometimes', 'numeric', 'min:0'],
            'reason_code' => ['required', 'string', Rule::in(self::REASON_CODES)],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason_code,other'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $reasonCode = (string) $this->input('reason_code', '');
            $note = trim((string) $this->input('note', ''));

            if ($reasonCode === 'other' && $note === '') {
                $validator->errors()->add('note', 'The note field is required when reason code is other.');
            }
        });
    }
}
