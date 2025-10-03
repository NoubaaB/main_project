<?php

namespace App\Models;

use App\Models\FishFamilly;
use App\Models\ChargeDetail;
use App\Models\ProductionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FishType extends Model
{
    /** @use HasFactory<\Database\Factories\FishTypeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function charge_details() : HasMany
    {
        return $this->hasMany(ChargeDetail::class, 'fish_type_id');    
    }

    function production_details() : HasMany
    {
        return $this->hasMany(ProductionDetail::class, 'fish_type_id');    
    }

    function fish_familly() : BelongsTo {
        return $this->belongsTo(FishFamilly::class, 'fish_familly_id'); 
        
    }
}
