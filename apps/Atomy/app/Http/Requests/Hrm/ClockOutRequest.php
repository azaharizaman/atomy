<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrm;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'string', 'exists:attendance,id'],
            'clock_out_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
