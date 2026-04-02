<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RfqDraftRequest extends FormRequest
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
        $tenantId = (string) $this->attributes->get('auth_tenant_id', '');
        $projectRule = Rule::exists('projects', 'id');
        if ($tenantId !== '') {
            $projectRule = $projectRule->where('tenant_id', $tenantId);
        }

        return [
            'title' => ['sometimes', 'filled', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'project_id' => ['sometimes', 'nullable', 'string', $projectRule],
            'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'savings_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'submission_deadline' => ['sometimes', 'date'],
            'closing_date' => ['sometimes', 'nullable', 'date'],
            'expected_award_at' => ['sometimes', 'nullable', 'date'],
            'technical_review_due_at' => ['sometimes', 'nullable', 'date'],
            'financial_review_due_at' => ['sometimes', 'nullable', 'date'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:64'],
            'evaluation_method' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
