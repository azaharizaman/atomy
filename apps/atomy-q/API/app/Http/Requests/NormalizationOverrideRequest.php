<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NormalizationOverrideReasonCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class NormalizationOverrideRequest extends FormRequest
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
            'override_data' => ['required', 'array', 'min:1'],
            'override_data.rfq_line_item_id' => ['sometimes', 'nullable', 'string'],
            'override_data.source_description' => ['sometimes', 'string', 'max:2000'],
            'override_data.quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'override_data.uom' => ['sometimes', 'nullable', 'string', 'max:32'],
            'override_data.unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reason_code' => ['required', 'string', Rule::in(NormalizationOverrideReasonCode::values())],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason_code,other'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $reasonCode = (string) $this->input('reason_code', '');
            $note = trim((string) $this->input('note', ''));

            if ($reasonCode !== 'other' || $note !== '') {
                return;
            }

            if ($validator->errors()->has('note')) {
                return;
            }

            $validator->errors()->add('note', 'The note field is required when reason code is other.');
        });
    }
}
