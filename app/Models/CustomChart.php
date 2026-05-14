<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomChart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'chart_type',
        'chart_options',
        'date_range_type',
        'fixed_month',
        'fixed_year',
        'dynamic_start',
        'dynamic_end',
        'display_type',
        'display_order',
        'grid_cols',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'chart_options'  => 'array',
        'is_active'      => 'boolean',
        'dynamic_start'  => 'date',
        'dynamic_end'    => 'date',
    ];

    public function series()
    {
        return $this->hasMany(CustomChartSeries::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
