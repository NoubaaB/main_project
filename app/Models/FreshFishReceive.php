<?php

namespace App\Models;

use App\Models\Supplier;
use App\Models\FreshFishReceiveDetail;
use App\Models\FreezeFishProcessDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FreshFishReceive extends Model
{
    /** @use HasFactory<\Database\Factories\FreshFishReceiveFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['freshFishReceiveDetails', 'freezeFishProcessDetails'];

    protected $guarded = [];

    // function provider(): BelongsTo
    // {
    //     return $this->belongsTo(Provider::class);
    // }

    function freshFishReceiveDetails(): HasMany
    {
        return $this->hasMany(FreshFishReceiveDetail::class);
    }

    function freezeFishProcessDetails(): HasMany
    {
        return $this->hasMany(FreezeFishProcessDetail::class);
    }

    function suppliers() : BelongsToMany 
    {
        return $this->belongsToMany(Supplier::class, "fresh_fish_receive_suppliers", "fresh_fish_receive_id", "supplier_id");
    }
}
