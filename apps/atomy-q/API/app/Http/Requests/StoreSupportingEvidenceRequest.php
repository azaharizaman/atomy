<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupportingEvidenceRequest extends FormRequest
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
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'file' => ['required', 'file', 'max:10240'],
            'vendor_id' => ['nullable', 'string'],
            'quote_submission_id' => ['nullable', 'string'],
            'award_id' => ['nullable', 'string'],
        ];
    }
}
