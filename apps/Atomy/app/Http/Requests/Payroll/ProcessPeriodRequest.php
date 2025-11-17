<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'pay_date' => ['required', 'date', 'after_or_equal:period_end'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['string', 'exists:employees,id'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'office_id' => ['nullable', 'string', 'exists:offices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_end.after' => 'Period end date must be after period start date.',
            'pay_date.after_or_equal' => 'Pay date must be on or after the period end date.',
        ];
    }
}
