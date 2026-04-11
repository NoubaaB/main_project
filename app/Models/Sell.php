<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Fishery;
use App\Models\Origin;
use App\Models\SellCategory;
use App\Models\Ship;
use App\Models\Truck;
use App\Models\NonWarehouseStockableOperation;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sell extends Model
{
    /** @use HasFactory<\Database\Factories\SellFactory> */
    use HasFactory , SoftDeletes;

    protected $with = ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations', 'clients'];

    protected $guarded = [];

    protected $hidden = ['trucks'];

    protected $appends = ['truck_ids', 'client_ids'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'is_warehouseable' => 'boolean',
        'note' => 'string',
        'unit_price'   => 'float',
        'total_price'  => 'float',
        'total_weight' => 'float',

    ];

    function warehouse_stockable_tracking_number_operations(): MorphMany
    {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'sourceable');
    }

    function non_warehouse_stockable_operations(): HasMany
    {
        return $this->hasMany(NonWarehouseStockableOperation::class);
    }

    function sell_category(): BelongsTo
    {
        return $this->belongsTo(SellCategory::class, 'sell_category_id');
    }

    function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, "sell_clients", "sell_id", "client_id")
            ->withPivot('sell_id', 'client_id');
    }

    function fishery(): BelongsTo
    {
        return $this->belongsTo(Fishery::class);
    }

    function ships(): BelongsToMany
    {
        return $this->belongsToMany(Ship::class, "sell_ships", "sell_id", "ship_id")
            ->withPivot('sell_id', 'ship_id');
    }

    function trucks(): BelongsToMany
    {
        return $this->belongsToMany(Truck::class, "truck_sell");
    }

    function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class, 'origin_id');
    }

    protected function truckIds(): Attribute
    {
        return Attribute::get(fn() => $this->trucks->pluck('id')->toArray());
    }

    protected function clientIds(): Attribute
    {
        return Attribute::get(fn() => $this->clients->pluck('id')->toArray());
    }

    function calc(): void
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $total_package_count = 0.00;
        foreach ($this->warehouse_stockable_tracking_number_operations as $warehouse_stockable_tracking_number_operation) {
            $total_weight += $warehouse_stockable_tracking_number_operation->total_weight;
            $total_price += $warehouse_stockable_tracking_number_operation->total_price;
            $total_package_count += $warehouse_stockable_tracking_number_operation->total_package_count;
        }

        foreach ($this->non_warehouse_stockable_operations as $non_warehouse_stockable_operation) {
            $total_weight += $non_warehouse_stockable_operation->total_weight;
            $total_price += $non_warehouse_stockable_operation->total_price;
            $total_package_count += $non_warehouse_stockable_operation->total_package_count;
        }

        $t = [
            "total_weight" => ceil($total_weight),
            "total_price" => ceil($total_price),
            "total_package_count" => ceil($total_package_count),
        ];
        $this->update($t);
    }
}
