<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FortnightActivity extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'workforce_id',
        'date',
        'fortnight_period',
        'section_id',
        'corporal_id',
        'price',
        'is_fixed_price',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'is_fixed_price' => 'boolean',
    ];

    public function workforce()
    {
        return $this->belongsTo(Workforce::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function corporal()
    {
        return $this->belongsTo(Corporal::class);
    }
}
