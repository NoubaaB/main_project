<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'warehouse_category_id',
        'warehouse_type', 'has_sejour', 'operation_service_fee',
        'wap_days', 'wap_sells_count', 'is_allow_price',
        'total_weight',
    ];

    protected $casts = [
        'has_sejour' => 'boolean',
        'is_allow_price' => 'boolean',
        'operation_service_fee' => 'decimal:2',
        'wap_days' => 'integer',
        'wap_sells_count' => 'integer',
        'total_weight' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(WarehouseCategory::class, 'warehouse_category_id');
    }

    public function balances()
    {
        return $this->hasMany(WarehouseBalance::class);
    }

    public function sejourRates()
    {
        return $this->hasMany(SejourRate::class);
    }

    /**
     * Recalculate total_weight from all non-finished balances.
     */
    public function recalculateTotalWeight(): void
    {
        $this->total_weight = round(
            $this->balances()
                ->where('is_finished', false)
                ->where('remaining_weight', '>', 0)
                ->sum('remaining_weight'),
            2
        );
        $this->saveQuietly(); // saveQuietly to avoid infinite loops
    }
}
