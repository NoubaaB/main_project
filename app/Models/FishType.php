<?php

namespace App\Models;

use App\Models\FreshFishReceiveDetail;
use App\Models\FreezeFishProcessDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FishType extends Model
{
    /** @use HasFactory<\Database\Factories\FishTypeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function fresh_fish_receive_details() : HasMany
    {
        return $this->hasMany(FreshFishReceiveDetail::class, 'fish_type_id');    
    }

    function freeze_fish_process_details() : HasMany
    {
        return $this->hasMany(FreezeFishProcessDetail::class, 'fish_type_id');    
    }
}
