<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FortnightSetting extends Model
{
    protected $fillable = [
        'q1_max_salary',
        'q2_max_salary',
        'date_start',
        'date_end',
    ];

    protected $casts = [
        'q1_max_salary' => 'decimal:2',
        'q2_max_salary' => 'decimal:2',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    /**
     * Scope: find the setting active for a given date.
     * Matches where date_start <= $date AND (date_end >= $date OR date_end IS NULL).
     */
    public function scopeActiveFor($query, $date)
    {
        return $query->where('date_start', '<=', $date)
                     ->where(function ($q) use ($date) {
                         $q->where('date_end', '>=', $date)
                           ->orWhereNull('date_end');
                     });
    }
}
