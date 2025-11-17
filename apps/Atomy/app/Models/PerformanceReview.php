<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\PerformanceReviewInterface;
use Nexus\Hrm\ValueObjects\ReviewStatus;
use Nexus\Hrm\ValueObjects\ReviewType;

class PerformanceReview extends Model implements PerformanceReviewInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'review_template_id',
        'review_period_start',
        'review_period_end',
        'review_type',
        'reviewer_id',
        'overall_score',
        'status',
        'submitted_at',
        'completed_at',
        'reviewer_comments',
        'employee_comments',
        'strengths',
        'areas_for_improvement',
        'goals_for_next_period',
        'metadata',
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'overall_score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getReviewType(): ReviewType
    {
        return ReviewType::from($this->review_type);
    }

    public function getReviewPeriodStart(): \DateTimeInterface
    {
        return $this->review_period_start;
    }

    public function getReviewPeriodEnd(): \DateTimeInterface
    {
        return $this->review_period_end;
    }

    public function getReviewerId(): string
    {
        return $this->reviewer_id;
    }

    public function getOverallScore(): ?float
    {
        return $this->overall_score ? (float) $this->overall_score : null;
    }

    public function getStatus(): ReviewStatus
    {
        return ReviewStatus::from($this->status);
    }

    public function getReviewerComments(): ?string
    {
        return $this->reviewer_comments;
    }

    public function getEmployeeComments(): ?string
    {
        return $this->employee_comments;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === ReviewStatus::DRAFT;
    }

    public function isPending(): bool
    {
        return $this->getStatus() === ReviewStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === ReviewStatus::COMPLETED;
    }
}
