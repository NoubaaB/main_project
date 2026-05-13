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
    ];

    protected $casts = [
        'is_migrant' => 'boolean',
        'salary_statement' => 'boolean',
        'fixed_daily_pay' => 'boolean',
        'daily_price' => 'decimal:2',
    ];

    public function fortnightActivities()
    {
        return $this->hasMany(FortnightActivity::class);
    }
}
