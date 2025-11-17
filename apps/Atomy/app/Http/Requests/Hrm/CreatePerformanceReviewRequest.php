<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Hrm\ValueObjects\ReviewType;

class CreatePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:employees,id'],
            'reviewer_id' => ['required', 'string', 'exists:employees,id'],
            'review_period_start' => ['required', 'date'],
            'review_period_end' => ['required', 'date', 'after:review_period_start'],
            'review_type' => ['required', 'string', 'in:' . implode(',', array_column(ReviewType::cases(), 'value'))],
            'reviewer_comments' => ['nullable', 'string'],
            'employee_comments' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'goals_for_next_period' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
