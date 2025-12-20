<?php

namespace App\Models;

use App\Models\TypeCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedCharge extends Model
{
    /** @use HasFactory<\Database\Factories\FixedChargeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'amount'   => 'float'
    ];

    function type_charge() : BelongsTo {
        return $this->belongsTo(ChargeCategory::class, 'charge_category_id');
    }
}
