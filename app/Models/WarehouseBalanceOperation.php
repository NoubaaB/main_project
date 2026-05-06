<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseBalanceOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        $recalc = function (WarehouseBalanceOperation $op) {
            $balance = $op->warehouseBalance;
            if ($balance) {
                $balance->recalculateRemaining(); // updates remaining_weight + is_finished + saves
                // The WarehouseBalance::updated event will then recalculate warehouse total_weight
            }
        };

        static::created($recalc);
        static::updated($recalc);
        static::deleted(function (WarehouseBalanceOperation $op) {
            $balance = WarehouseBalance::find($op->warehouse_balance_id);
            if ($balance) {
                $balance->recalculateRemaining();
            }
        });
    }

    protected $fillable = [
        'warehouse_balance_id', 'date',
        'weight', 'unit_price', 'total_price', 'package_count',
        'source_type', 'source_id', 'client_name',
    ];

    protected $casts = [
        'date' => 'date',
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'package_count' => 'decimal:2',
    ];

    public function warehouseBalance()
    {
        return $this->belongsTo(WarehouseBalance::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
