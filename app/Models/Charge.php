<?php

namespace App\Models;

use App\Models\Origin;
use App\Models\Supplier;
use App\Models\TypeCharge;
use App\Models\ChargeDetail;
use App\Models\ProductionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Charge extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['charge_details', 'production_details'];

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'is_stockable' => 'boolean',
        'note' => 'string',
        'total_price' => 'double',
        'total_weight' => 'double'
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

    function charge_details(): HasMany
    {
        return $this->hasMany(ChargeDetail::class);
    }

    function production_details(): HasMany
    {
        return $this->hasMany(ProductionDetail::class);
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

    public function calc()
    {
        $total_price = 0;
        $total_weight = 0;
        foreach ($this->charge_details as $detail) {
            $total_price += $detail->price;
            $total_weight += $detail->weight;
        }
        $this->charge_details_total_price = $total_price;
        $this->charge_details_total_weight = $total_weight;
        $this->save();
    }
}
