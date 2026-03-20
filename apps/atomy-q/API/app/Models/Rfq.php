<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfq extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'rfqs';

    protected $fillable = [
        'tenant_id',
        'rfq_number',
        'title',
        'description',
        'category',
        'department',
        'status',
        'owner_id',
        'project_id',
        'estimated_value',
        'savings_percentage',
        'submission_deadline',
        'closing_date',
        'expected_award_at',
        'technical_review_due_at',
        'financial_review_due_at',
        'payment_terms',
        'evaluation_method',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
        'submission_deadline' => 'datetime',
        'closing_date' => 'datetime',
        'expected_award_at' => 'datetime',
        'technical_review_due_at' => 'datetime',
        'financial_review_due_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return HasMany<RfqLineItem>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(RfqLineItem::class, 'rfq_id');
    }

    /**
     * @return HasMany<QuoteSubmission>
     */
    public function quoteSubmissions(): HasMany
    {
        return $this->hasMany(QuoteSubmission::class, 'rfq_id');
    }

    /**
     * @return HasMany<ComparisonRun>
     */
    public function comparisonRuns(): HasMany
    {
        return $this->hasMany(ComparisonRun::class, 'rfq_id');
    }

    /**
     * @return HasMany<Approval>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'rfq_id');
    }

    /**
     * @return HasMany<VendorInvitation>
     */
    public function vendorInvitations(): HasMany
    {
        return $this->hasMany(VendorInvitation::class, 'rfq_id');
    }
}
