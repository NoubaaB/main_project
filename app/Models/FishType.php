<?php

namespace App\Models;

use App\Models\FishFamilly;
use App\Models\FishProcessType;
use Illuminate\Database\Eloquent\Model;
use App\Models\NonWarehouseChargeOperation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FishType extends Model
{
    /** @use HasFactory<\Database\Factories\FishTypeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function non_warehouse_charge_items() : HasMany
    {
        return $this->hasMany(NonWarehouseChargeOperation::class, 'fish_type_id');    
    }

    function warehouse_fish_tracking_number_operations() : HasMany
    {
        return $this->hasMany(WarehouseFishTrackingNumberOperation::class, 'fish_type_id');    
    }

    function fish_familly() : BelongsTo {
        return $this->belongsTo(FishFamilly::class, 'fish_familly_id'); 
    }

    function fish_process_type() : BelongsTo {
        return $this->belongsTo(FishProcessType::class, 'fish_process_type_id'); 
    }
}
