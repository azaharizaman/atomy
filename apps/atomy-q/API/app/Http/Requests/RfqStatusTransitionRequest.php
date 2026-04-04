<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Sourcing\ValueObjects\RfqStatus;
use Illuminate\Validation\Rule;

final class RfqStatusTransitionRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(RfqStatus::ALL)],
        ];
    }
}
