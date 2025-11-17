<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;

class CreateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:employees,id'],
            'leave_type_id' => ['required', 'string', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot be requested for past dates.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ];
    }
}
