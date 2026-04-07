<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class BackupCode extends Model
{
    use HasUlids;

    protected $table = 'mfa_backup_codes';
    protected $fillable = ['user_id', 'code_hash', 'used_at'];
    protected $casts = ['used_at' => 'datetime'];
}
