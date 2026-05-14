<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaticCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_fr',
        'name_es',
        'amount',
        'date_range_type',
        'date_start',
        'date_end',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'date_start' => 'date',
        'date_end'   => 'date',
        'is_active'  => 'boolean',
    ];

    /**
     * Scope: find static charges active for a given date.
     * - all_time: always active
     * - date_range: date_start <= $date AND date_end >= $date
     * - ongoing: date_start <= $date (no end)
     */
    public function scopeActiveFor($query, $date)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) use ($date) {
                         // all_time — always active
                         $q->where('date_range_type', 'all_time')
                           // date_range — within bounds
                           ->orWhere(function ($q2) use ($date) {
                               $q2->where('date_range_type', 'date_range')
                                  ->where('date_start', '<=', $date)
                                  ->where('date_end', '>=', $date);
                           })
                           // ongoing — started, no end
                           ->orWhere(function ($q2) use ($date) {
                               $q2->where('date_range_type', 'ongoing')
                                  ->where('date_start', '<=', $date);
                           });
                     });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
