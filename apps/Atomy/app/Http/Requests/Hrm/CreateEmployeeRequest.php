<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id ?? '';
        
        return [
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code,NULL,id,tenant_id,' . $tenantId],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email,NULL,id,tenant_id,' . $tenantId],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'hire_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:' . implode(',', array_column(EmployeeStatus::cases(), 'value'))],
            'manager_id' => ['nullable', 'string', 'exists:employees,id'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'office_id' => ['nullable', 'string', 'exists:offices,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', 'string', 'in:' . implode(',', array_column(EmploymentType::cases(), 'value'))],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.unique' => 'The employee code is already in use for this tenant.',
            'email.unique' => 'The email address is already in use for this tenant.',
            'date_of_birth.before' => 'The date of birth must be before today.',
        ];
    }
}
