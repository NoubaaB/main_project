<?php

namespace App\Models;

use App\Models\Provider;
use App\Models\FreezeFishProcessDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreezeFishProcess extends Model
{
    /** @use HasFactory<\Database\Factories\FreezeFishProcessFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    function freezeFishProcessDetails(): HasMany
    {
        return $this->hasMany(FreezeFishProcessDetail::class);
    }
}
