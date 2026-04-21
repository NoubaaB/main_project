<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corporal extends Model
{
    protected $fillable = [
        'full_name',
        'sex',
        'cni',
        'is_quinzaine',
    ];

    protected $casts = [
        'is_quinzaine' => 'boolean',
    ];

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_corporals');
    }

    public function fortnightActivities()
    {
        return $this->hasMany(FortnightActivity::class);
    }
}
