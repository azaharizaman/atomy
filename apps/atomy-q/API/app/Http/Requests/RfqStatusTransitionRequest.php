<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Nexus\Sourcing\ValueObjects\RfqStatus;
use Illuminate\Validation\Rule;

/**
 * Validates requested RFQ status transitions against the sourcing domain values.
 *
 * The transition policy is enforced after validation; this request only rejects
 * unknown statuses before domain orchestration runs.
 */
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
