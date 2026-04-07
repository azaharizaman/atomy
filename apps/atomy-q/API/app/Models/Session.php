<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $table = 'sessions';
    protected $fillable = ['id', 'user_id', 'tenant_id', 'payload', 'last_activity'];
    protected $casts = ['payload' => 'array', 'last_activity' => 'datetime'];
}
