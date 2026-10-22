<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workforce extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'full_name',
        'matricule',
        'nationality',
        'sex',
        'cni',
        'is_migrant',
        'salary_statement',
        'fixed_daily_pay',
        'cnss_id',
        'daily_price',
        'status',
        'departure_date',
        'departure_reason',
        'return_date',
        'previous_team_ids',
    ];

    protected $casts = [
        'is_migrant' => 'boolean',
        'salary_statement' => 'boolean',
        'fixed_daily_pay' => 'boolean',
        'daily_price' => 'decimal:2',
        'departure_date' => 'date',
        'return_date' => 'date',
        'previous_team_ids' => 'array',
    ];

    public function fortnightActivities()
    {
        return $this->hasMany(FortnightActivity::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_workforce')->withTimestamps();
    }

    public function movements()
    {
        return $this->hasMany(WorkforceMovement::class);
    }
}
