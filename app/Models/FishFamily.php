<?php

namespace App\Models;

use App\Models\FishType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FishFamily extends Model
{
    /** @use HasFactory<\Database\Factories\FishFamilyFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    protected $appends = ['name'];

    public function getNameAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->attributes['name_' . $locale]
            ?? $this->attributes['name_fr']
            ?? $this->attributes['name_en']
            ?? $this->attributes['name_es']
            ?? null;
    }

    function fish_types() : HasMany {
        return $this->hasMany(FishType::class);
    }
}
