<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function charges(): BelongsToMany
    {
        return $this->belongsToMany(Charge::class, "charge_suppliers", "supplier_id", "charge_id");
    }
    
}
