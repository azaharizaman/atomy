<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class NotificationTemplate extends Model
{
    use HasUlids;

    protected $fillable = [
        'type',
        'name',
        'category',
        'email_content',
        'sms_content',
        'push_content',
        'in_app_content',
        'variables',
        'locale',
        'is_active',
    ];

    protected $casts = [
        'email_content' => 'array',
        'push_content' => 'array',
        'in_app_content' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
