<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Truck extends Model
{
    /** @use HasFactory<\Database\Factories\TruckFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function charges() : BelongsToMany {
        return $this->belongsToMany(Charge::class , 'truck_charge');
    }

    function sells() : BelongsToMany {
        return $this->belongsToMany(Truck::class, "truck_sell");
    }
}
