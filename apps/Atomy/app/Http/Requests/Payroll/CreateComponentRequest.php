<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

class CreateComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:payroll_components,code,NULL,id,tenant_id,' . auth()->user()->tenant_id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:' . implode(',', array_column(ComponentType::cases(), 'value'))],
            'calculation_method' => ['required', 'string', 'in:' . implode(',', array_column(CalculationMethod::cases(), 'value'))],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage_of' => ['nullable', 'string', 'in:basic,gross,component'],
            'percentage_value' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reference_component_id' => ['nullable', 'string', 'exists:payroll_components,id'],
            'formula' => ['nullable', 'string'],
            'is_statutory' => ['boolean'],
            'is_taxable' => ['boolean'],
            'is_active' => ['boolean'],
            'display_order' => ['integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'The component code is already in use for this tenant.',
        ];
    }
}
