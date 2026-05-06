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
    ];

    protected $casts = [
        'is_migrant' => 'boolean',
    ];

    public function fortnightActivities()
    {
        return $this->hasMany(FortnightActivity::class);
    }
}
