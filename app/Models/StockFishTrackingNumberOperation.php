<?php

namespace App\Models;

use App\Models\Package;
use App\Models\StockFishTrackingNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockFishTrackingNumberOperation extends Model
{
    /** @use HasFactory<\Database\Factories\StockFishTrackingNumberOperationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->checkDateInS();
        });

        static::saved(function ($model) {
            $model->checkdateOFT();
        });

        static::deleting(function ($model) {
            if($model->type_operation == 'in'){
                $model->checkDateInS();
            }else{
                $model->checkdateOFT();
            }
        });
    }

    function stock_fish_tracking_number() : BelongsTo {
        return $this->belongsTo(StockFishTrackingNumber::class);
    }

    function operationable() : MorphTo {
        return $this->morphTo();
    }

    function package() : BelongsTo {
        return $this->belongsTo(Package::class);
    }

    function checkDateInS(): void
    {
        $s_f_t_o = $this->stock_fish_tracking_number
            ->stock_fish_tracking_number_operations()
            ->where("type_operation", "in")
            ->first();

        if ($this->type_operation == 'in' && $this->stock_fish_tracking_number->date_in_stock == null && $s_f_t_o) {
            $this->stock_fish_tracking_number->update([
                "date_in_stock" => $s_f_t_o->date
            ]);
        }
    }

    function checkdateOFT() : void {
        $total_weight = 0.00;
        $s_f_t_n_os = $this->stock_fish_tracking_number->stock_fish_tracking_number_operations;
        foreach ($s_f_t_n_os as $s_f_t_n_o) {
            if($s_f_t_n_o->type_operation == 'in'){
                $total_weight+= $s_f_t_n_o->total_weight;
            }else{
                $total_weight-= $s_f_t_n_o->total_weight;
            }
        }
        if($total_weight <= 0){
            $this->stock_fish_tracking_number->update([
                "date_out_of_stock" => $this->date
            ]);
        }
    }

    function calc(): array
    {
        if($this->type_operation == 'out'){
            return [
                "total_weight" => 0.00,
                "total_price" => 0.00,
                "unit_price" => 0.00,
                "total_package_count" => 0.00
            ];
        }else{
            return [
                "total_weight" => $this->total_weight,
                "total_price" => $this->total_price,
                "unit_price" => $this->unit_price,
                "total_package_count" => $this->total_package_count
            ];
        }
            
    }
}
