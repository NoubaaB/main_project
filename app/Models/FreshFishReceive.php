<?php

namespace App\Models;

use App\Models\Supplier;
use App\Models\FreshFishReceiveDetail;
use App\Models\FreezeFishProcessDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FreshFishReceive extends Model
{
    /** @use HasFactory<\Database\Factories\FreshFishReceiveFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['fresh_fish_receive_details', 'freeze_fish_process_details'];

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

    function fresh_fish_receive_details(): HasMany
    {
        return $this->hasMany(FreshFishReceiveDetail::class);
    }

    function freeze_fish_process_details(): HasMany
    {
        return $this->hasMany(FreezeFishProcessDetail::class);
    }

    function suppliers() : BelongsToMany 
    {
        return $this->belongsToMany(Supplier::class, "fresh_fish_receive_suppliers", "fresh_fish_receive_id", "supplier_id");
    }

    //private functions

    public function calc()
    {
        $total_price = 0;
        $total_weight = 0;
        foreach ($this->fresh_fish_receive_details as $detail) {
            $total_price += $detail->price;
            $total_weight += $detail->weight;
        }
        $this->total_price = $total_price;
        $this->total_weight = $total_weight;
        $this->save();
    }
}
