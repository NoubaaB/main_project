<?php

namespace App\Models;

use App\Models\Origin;
use App\Models\Supplier;
use App\Models\TypeCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StockFishTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Charge extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['stock_fish_tracking_number_operations'];

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'is_stockable' => 'boolean',
        'note' => 'string',
        'charge_details_total_price' => 'double',
        'charge_details_total_weight' => 'double'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // $model->calc();
        });

        static::saving(function ($model) {
        });

        static::deleted(function ($model) {
        });
    }

    function type_charge() : BelongsTo {
        return $this->belongsTo(TypeCharge::class, 'type_charge_id'); 
    }

    function stock_fish_tracking_number_operations() : MorphMany {
        return $this->morphMany(StockFishTrackingNumberOperation::class, 'operationable');
    }

    function suppliers() : BelongsToMany 
    {
        return $this->belongsToMany(Supplier::class, "charge_suppliers", "charge_id", "supplier_id")
                ->withPivot('charge_id', 'supplier_id');
    }

    function origin() : BelongsTo {
        return $this->belongsTo(Origin::class, 'origin_id'); 
    }

    //private functions

    function calc() : void {
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
