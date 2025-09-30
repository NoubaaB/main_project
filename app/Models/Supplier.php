<?php

namespace App\Models;

use App\Models\FreshFishReceive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function fresh_fish_receives(): BelongsToMany
    {
        return $this->belongsToMany(FreshFishReceive::class, "fresh_fish_receive_suppliers", "supplier_id", "fresh_fish_receive_id");
    }
    
}
