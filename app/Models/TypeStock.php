<?php

namespace App\Models;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeStock extends Model
{
    /** @use HasFactory<\Database\Factories\TypeStockFactory> */
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    function stock() : HasMany {
        return $this->hasMany(Stock::class);
        
    }
}
