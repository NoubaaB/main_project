<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockReset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'reset_date',
        'reason',
        'created_by',
        'total_weight_cleared',
        'total_price_cleared',
        'articles_count',
        'balances_affected_count',
    ];

    protected $casts = [
        'reset_date' => 'date',
        'total_weight_cleared' => 'decimal:2',
        'total_price_cleared' => 'decimal:2',
        'articles_count' => 'integer',
        'balances_affected_count' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operations()
    {
        return $this->hasMany(WarehouseBalanceOperation::class, 'source_id')
            ->where('source_type', 'WarehouseStockReset');
    }

    /**
     * If a warehouse has an active stock reset checkpoint on or after the balance's date,
     * immediately create a closing reset operation so that this stock does NOT bleed
     * into the period on or after the reset checkpoint date.
     */
    public static function applyResetToBalance(WarehouseBalance $balance): void
    {
        if ($balance->warehouse_operation !== 'stock_in') {
            return;
        }

        // Avoid double-applying
        $hasResetOp = $balance->operations()
            ->where('source_type', 'WarehouseStockReset')
            ->exists();
        if ($hasResetOp) {
            return;
        }

        $balanceDate = $balance->date instanceof \Carbon\Carbon
            ? $balance->date->toDateString()
            : (string) $balance->date;

        // Find the earliest reset checkpoint on or after balance date
        $reset = static::where('warehouse_id', $balance->warehouse_id)
            ->where('reset_date', '>=', $balanceDate)
            ->orderBy('reset_date', 'asc')
            ->first();

        if ($reset) {
            $resetDate = $reset->reset_date instanceof \Carbon\Carbon
                ? $reset->reset_date->toDateString()
                : (string) $reset->reset_date;

            WarehouseBalanceOperation::create([
                'warehouse_balance_id' => $balance->id,
                'date' => $resetDate,
                'weight' => $balance->remaining_weight,
                'unit_price' => $balance->unit_price ?? 0,
                'total_price' => $balance->effective_total_price ?: round(($balance->unit_price ?? 0) * (float) $balance->remaining_weight, 2),
                'package_count' => $balance->package_count ?? 0,
                'source_type' => 'WarehouseStockReset',
                'source_id' => $reset->id,
                'client_name' => 'Stock Reset Checkpoint',
            ]);

            $balance->recalculateRemaining();
        }
    }
}
