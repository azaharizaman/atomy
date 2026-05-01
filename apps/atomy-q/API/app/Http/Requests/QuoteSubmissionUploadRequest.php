<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates quote upload metadata and the supplier document file.
 *
 * Tenant ownership is enforced by the consuming service/controller; this request
 * only validates the RFQ/vendor identifiers and attached file shape.
 */
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
