<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\QuoteIntake\NormalizationIssueCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'override_data' => ['required', 'array'],
            'issue_code' => ['nullable', 'string', Rule::in(NormalizationIssueCode::all())],
        ];
    }
}
