<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChargeCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeCategoryFactory> */
    use HasFactory , SoftDeletes;
    
    protected $guarded = [];

    function charges() : HasMany {
        return $this->hasMany(Charge::class, 'charge_category_id');
        
    }
}
