<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkforceFortnightPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workforce_id',
        'year',
        'month',
        'fortnight_period',
        'is_paid',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function workforce()
    {
        return $this->belongsTo(Workforce::class);
    }
}
