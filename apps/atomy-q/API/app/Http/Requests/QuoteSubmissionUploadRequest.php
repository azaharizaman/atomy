<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class QuoteSubmissionUploadRequest extends FormRequest
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
            'rfq_id' => ['required', 'string'],
            'vendor_id' => ['required', 'string'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file'],
        ];
    }
}
