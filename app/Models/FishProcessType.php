<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FishProcessType extends Model
{
    /** @use HasFactory<\Database\Factories\FishProcessTypeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function fish_types() : HasMany {
        return $this->hasMany(FishType::class);
    }
}
