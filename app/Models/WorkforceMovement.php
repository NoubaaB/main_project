<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkforceMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workforce_id',
        'type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'notes',
        'restored_teams',
        'previous_team_ids',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_count' => 'integer',
        'restored_teams' => 'boolean',
        'previous_team_ids' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['is_ongoing'];

    public function getIsOngoingAttribute(): bool
    {
        return (bool) ($this->metadata['is_ongoing'] ?? false);
    }

    public function workforce()
    {
        return $this->belongsTo(Workforce::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
