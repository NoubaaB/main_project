<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'name',
        'daily_price',
        'is_price_modifiable',
    ];

    protected $casts = [
        'daily_price' => 'decimal:2',
        'is_price_modifiable' => 'boolean',
    ];

    public function corporals()
    {
        return $this->belongsToMany(Corporal::class, 'section_corporals');
    }

    public function fortnightActivities()
    {
        return $this->hasMany(FortnightActivity::class);
    }
}
