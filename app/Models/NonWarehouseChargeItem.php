<?php

namespace App\Models;

use App\Models\Package;
use App\Models\FishType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NonWarehouseChargeItem extends Model
{
    /** @use HasFactory<\Database\Factories\NonWarehouseChargeItemFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];


    function charge() : BelongsTo {
        return $this->belongsTo(Charge::class);
    }

    function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    function fish_type(): BelongsTo
    {
        return $this->belongsTo(FishType::class);
    }
}

