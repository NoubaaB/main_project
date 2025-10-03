<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeCharge extends Model
{
    /** @use HasFactory<\Database\Factories\TypeChargeFactory> */
    use HasFactory , SoftDeletes;
    
    protected $guarded = [];

    function charges() : HasMany {
        return $this->hasMany(Charge::class, 'type_charge_id');
        
    }
}
