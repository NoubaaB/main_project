<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Production extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['supplier_ids', 'truck_ids', 'created_by_name'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'reception_tracking_number' => 'array',
        'tracking_onp_numbers' => 'array',
        'production_tracking_numbers' => 'array',
        'note' => 'string',
        'total_price'  => 'float',
        'total_weight' => 'float',
        'total_package_count' => 'float',
    ];

    // ── Relationships ──

    function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    function production_category(): BelongsTo
    {
        return $this->belongsTo(ProductionCategory::class, 'production_category_id');
    }

    function non_warehouse_stockable_operations(): HasMany
    {
        return $this->hasMany(NonWarehouseStockableOperation::class);
    }

    function warehouse_stockable_tracking_number_operations(): MorphMany
    {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'sourceable');
    }

    function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'production_supplier', 'production_id', 'supplier_id');
    }

    function trucks(): BelongsToMany
    {
        return $this->belongsToMany(Truck::class, 'production_truck');
    }

    function fishery(): BelongsTo
    {
        return $this->belongsTo(Fishery::class);
    }

    function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class, 'origin_id');
    }

    function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Accessors ──

    protected function supplierIds(): Attribute
    {
        return Attribute::get(fn() => $this->suppliers->pluck('id')->toArray());
    }

    protected function truckIds(): Attribute
    {
        return Attribute::get(fn() => $this->trucks->pluck('id')->toArray());
    }

    protected function createdByName(): Attribute
    {
        return Attribute::get(fn() => $this->creator?->name ?? null);
    }

    // ── Calc ──

    function calc(): void
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $total_package_count = 0.00;

        foreach ($this->warehouse_stockable_tracking_number_operations as $op) {
            $total_weight += $op->total_weight;
            $total_price += $op->total_price;
            $total_package_count += $op->total_package_count;
        }

        foreach ($this->non_warehouse_stockable_operations as $op) {
            $total_weight += $op->total_weight;
            $total_price += $op->total_price;
            $total_package_count += $op->total_package_count;
        }

        $this->update([
            'total_weight' => $total_weight,
            'total_price' => $total_price,
            'total_package_count' => $total_package_count,
        ]);
    }
}
