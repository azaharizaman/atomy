<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('id');
        
        return [
            'employee_code' => ['sometimes', 'string', 'max:50', 'unique:employees,employee_code,' . $employeeId . ',id,tenant_id,' . auth()->user()->tenant_id],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:employees,email,' . $employeeId . ',id,tenant_id,' . auth()->user()->tenant_id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', array_column(EmployeeStatus::cases(), 'value'))],
            'manager_id' => ['nullable', 'string', 'exists:employees,id'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'office_id' => ['nullable', 'string', 'exists:offices,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['sometimes', 'string', 'in:' . implode(',', array_column(EmploymentType::cases(), 'value'))],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
