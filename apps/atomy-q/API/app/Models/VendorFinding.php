<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VendorFinding extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'vendor_findings';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'domain',
        'issue_type',
        'severity',
        'status',
        'opened_at',
        'opened_by',
        'remediation_owner',
        'remediation_due_at',
        'resolution_summary',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'remediation_due_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            self::normalizeIdentifier($record, 'id');
            self::normalizeIdentifier($record, 'tenant_id');
            self::normalizeIdentifier($record, 'vendor_id');
        });
    }

    private static function normalizeIdentifier(self $record, string $attribute): void
    {
        $value = trim((string) $record->getAttribute($attribute));
        if ($value !== '') {
            $record->setAttribute($attribute, strtolower($value));
        }
    }
}
