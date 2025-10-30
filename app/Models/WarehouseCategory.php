<?php

namespace App\Models;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseCategory extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseCategoryFactory> */
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    function warehouse() : HasMany {
        return $this->hasMany(Warehouse::class);
        
    }
}
