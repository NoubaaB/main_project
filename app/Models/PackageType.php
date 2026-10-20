<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\NonWarehouseStockableOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\WarehouseStockableTrackingNumberOperation;

class PackageType extends Model
{
    /** @use HasFactory<\Database\Factories\PackageTypeFactory> */
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
            ?? $this->attributes['name_in_invoice']
            ?? ($this->attributes['size'] ? (string) $this->attributes['size'] : null);
    }
    
    function warehouse_stockable_tracking_number_operations() : MorphMany {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, "stockable");
    }

    function non_warehouse_stockable_operation(): MorphMany
    {
        return $this->morphMany(NonWarehouseStockableOperation::class, 'stockable');
    }

    public function secondaryConfigs(): HasMany
    {
        return $this->hasMany(PackageSecondaryConfig::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PackageTypeComponent::class, 'package_type_id');
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(PackageTypeComponent::class, 'target_package_type_id');
    }
}
