<?php

declare(strict_types=1);

namespace App\Models\Infrastructure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * EventProjection Model
 * 
 * Tracks the progress of projection rebuilds.
 * Stores the last processed event ID for each projector.
 * 
 * @property string $id
 * @property string $projector_name
 * @property string|null $last_processed_event_id
 * @property int $last_processed_version
 * @property \Carbon\Carbon|null $last_processed_at
 * @property string $status
 * @property string|null $error_message
 */
final class EventProjection extends Model
{
    use HasFactory;
    
    protected $table = 'event_projections';
    
    protected $fillable = [
        'projector_name',
        'last_processed_event_id',
        'last_processed_version',
        'last_processed_at',
        'status',
        'error_message',
    ];
    
    protected $casts = [
        'last_processed_version' => 'integer',
        'last_processed_at' => 'datetime',
    ];
}
