<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomChartSeries extends Model
{
    use HasFactory;

    protected $table = 'custom_chart_series';

    protected $fillable = [
        'custom_chart_id',
        'name',
        'series_type',
        'color',
        'measure_unit',
        'is_visible',
        'data_source',
        'aggregate_field',
        'aggregate_function',
        'group_by',
        'filters',
        'is_formula',
        'formula_expression',
        'formula_series_refs',
        'sort_order',
    ];

    protected $casts = [
        'filters'             => 'array',
        'formula_series_refs' => 'array',
        'is_formula'          => 'boolean',
        'is_visible'          => 'boolean',
    ];

    public function customChart()
    {
        return $this->belongsTo(CustomChart::class);
    }
}
