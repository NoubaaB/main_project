<?php

namespace App\Models;

use App\Models\FishFamily;
use App\Models\FishProcessType;
use App\Models\WarehouseStockable;
use Illuminate\Database\Eloquent\Model;
use App\Models\NonWarehouseStockableOperation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FishType extends Model
{
    /** @use HasFactory<\Database\Factories\FishTypeFactory> */
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
            ?? null;
    }

    function nonWarehouse_stockable_operations() : HasMany
    {
        return $this->hasMany(NonWarehouseStockableOperation::class);    
    }

    function warehouse_stockable_tracking_number_operations() : MorphMany
    {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'stockable');  
    }

    function non_warehouse_stockable_operation() : MorphMany
    {
        return $this->morphMany(NonWarehouseStockableOperation::class, 'stockable');  
    }

    function warehouse_stockables() : MorphMany
    {
        return $this->morphMany(WarehouseStockable::class, 'stockable');  
    }

    function fish_family() : BelongsTo {
        return $this->belongsTo(FishFamily::class, 'fish_family_id'); 
    }

    function fish_process_type() : BelongsTo {
        return $this->belongsTo(FishProcessType::class, 'fish_process_type_id'); 
    }

    function waps() : HasMany {
        return $this->hasMany(FishTypeWap::class);
    }
}
