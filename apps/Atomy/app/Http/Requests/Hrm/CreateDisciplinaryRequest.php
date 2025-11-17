<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Hrm\ValueObjects\DisciplinarySeverity;

class CreateDisciplinaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:employees,id'],
            'reported_by' => ['required', 'string', 'exists:employees,id'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', 'max:100'],
            'severity' => ['required', 'string', 'in:' . implode(',', array_column(DisciplinarySeverity::cases(), 'value'))],
            'description' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
