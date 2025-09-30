<?php

namespace App\Models;

use App\Models\Provider;
use App\Models\FreezeFishProcessDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreezeFishProcess extends Model
{
    /** @use HasFactory<\Database\Factories\FreezeFishProcessFactory> */
    use HasFactory , SoftDeletes;

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

        static::saving(function ($model) {});

        static::deleted(function ($model) {});
    }

    function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    function freeze_fish_process_details(): HasMany
    {
        return $this->hasMany(FreezeFishProcessDetail::class);
    }

    //private functions

    public function calc()
    {
        $total_price = 0;
        $total_weight = 0;
        foreach ($this->freeze_fish_process_details as $detail) {
            $total_price += $detail->price;
            $total_weight += $detail->weight;
        }
        $this->total_price = $total_price;
        $this->total_weight = $total_weight;
        $this->save();
    }   
}
