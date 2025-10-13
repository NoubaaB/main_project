<?php

namespace App\Models;

use App\Models\Client;
use App\Models\SellDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StockFishTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sell extends Model
{
    /** @use HasFactory<\Database\Factories\SellFactory> */
    use HasFactory , SoftDeletes;

    protected $with = ['sell_details'];

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'is_stockable' => 'boolean',
        'note' => 'string',
        'sell_details_total_price' => 'double',
        'sell_details_total_weight' => 'double'
    ];

    function stock_fish_tracking_number_operations(): MorphMany
    {
        return $this->morphMany(StockFishTrackingNumberOperation::class, 'operationable');
    }

    function type_sell(): BelongsTo
    {
        return $this->belongsTo(TypeSell::class, 'type_sell_id');
    }

    function sell_details(): HasMany
    {
        return $this->hasMany(SellDetail::class);
    }

    function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, "sell_clients", "sell_id", "client_id")
            ->withPivot('sell_id', 'client_id');
    }

    function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class, 'origin_id');
    }

    function calc(): void
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $total_package_count = 0.00;
        foreach ($this->stock_fish_tracking_number_operations as $stock_fish_tracking_number_operation) {
            $total_weight += $stock_fish_tracking_number_operation->total_weight;
            $total_price += $stock_fish_tracking_number_operation->total_price;
            $total_package_count += $stock_fish_tracking_number_operation->total_package_count;
        }

        $t = [
            "total_weight" => ceil($total_weight),
            "total_price" => ceil($total_price),
            "total_package_count" => ceil($total_package_count),
        ];
        $this->update($t);
    }
}
