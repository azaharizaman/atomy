<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'note' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
