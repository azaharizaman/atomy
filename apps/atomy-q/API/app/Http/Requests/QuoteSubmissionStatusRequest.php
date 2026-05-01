<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Restricts quote submission status changes to known intake states.
 *
 * These values mirror the upload, extraction, normalization, review, readiness,
 * acceptance, and failure states used by the quote-ingestion pipeline.
 */
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
                    'accepted',
                    'failed',
                ]),
            ],
        ];
    }
}
