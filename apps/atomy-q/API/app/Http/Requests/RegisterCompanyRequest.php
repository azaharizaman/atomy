<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterCompanyRequest extends FormRequest
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
            'tenant_code' => [
                'required',
                'string',
                'min:2',
                'max:64',
                'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
            ],
            'company_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'owner_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'owner_email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'owner_password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],
            'timezone' => [
                'nullable',
                'string',
                'max:64',
            ],
            'locale' => [
                'nullable',
                'string',
                'max:16',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
        ];
    }
}
