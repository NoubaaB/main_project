<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'corporal_id',
        'description',
    ];

    protected $casts = [
        'corporal_id' => 'integer',
    ];

    /**
     * Chef of team (Corporal).
     */
    public function corporal()
    {
        return $this->belongsTo(Corporal::class);
    }

    /**
     * Workforces belonging to this team.
     */
    public function workforces()
    {
        return $this->belongsToMany(Workforce::class, 'team_workforce')->withTimestamps();
    }
}
