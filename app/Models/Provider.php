<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function fresh_fish_receives() : HasMany
    {
        return $this->hasMany(FreshFishReceive::class);
        
    }
}
