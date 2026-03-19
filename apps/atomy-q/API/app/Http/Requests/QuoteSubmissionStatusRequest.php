<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QuoteSubmissionStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                Rule::in([
                    'uploaded',
                    'extracting',
                    'extracted',
                    'normalizing',
                    'needs_review',
                    'ready',
                    'failed',
                ]),
            ],
        ];
    }
}
