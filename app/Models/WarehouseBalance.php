<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        $recalc = function (WarehouseBalance $balance) {
            if ($balance->warehouse) {
                $balance->warehouse->recalculateTotalWeight();
            }
        };

        static::creating(function (WarehouseBalance $balance) {
            if ($balance->stockable_type && str_contains($balance->stockable_type, 'FishType')) {
                $wap = FishTypeWap::getWapForFishType($balance->stockable_id, $balance->warehouse_id, $balance->date ?? now());
                if ($wap !== null) {
                    $balance->unit_price = $wap;
                    $balance->total_price = round($wap * (float)$balance->total_weight, 2);
                }
            }
        });

        static::created(function (WarehouseBalance $balance) use ($recalc) {
            $recalc($balance);
            \App\Models\WarehouseStockReset::applyResetToBalance($balance);
        });
        static::updated($recalc);
        static::deleted(function (WarehouseBalance $balance) {
            // After delete, warehouse relation may still be loaded
            $warehouse = Warehouse::find($balance->warehouse_id);
            if ($warehouse) {
                $warehouse->recalculateTotalWeight();
            }
        });
    }

    protected $fillable = [
        'warehouse_id', 'stockable_type', 'stockable_id',
        'reception_tracking_number', 'warehouse_operation',
        'date', 'total_weight', 'remaining_weight',
        'total_price', 'unit_price', 'package_count', 'package_type_id',
        'supplier_name', 'origin_name', 'fishery_name', 'client_name',
        'source_type', 'source_id', 'is_finished',
    ];

    protected $casts = [
        'date' => 'date',
        'total_weight' => 'decimal:2',
        'remaining_weight' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'package_count' => 'decimal:2',
        'is_finished' => 'boolean',
    ];

    protected $appends = ['wap', 'effective_total_price', 'clean_stockable_type', 'clean_source_type', 'article_name', 'lot_number'];

    public function getLotNumberAttribute(): string
    {
        $raw = $this->reception_tracking_number;
        if (empty($raw)) return '-';
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return implode(', ', array_filter(array_map('trim', $decoded)));
        }
        return trim(str_replace(['[', ']', '"', "'", '\\'], '', (string)$raw));
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockable()
    {
        return $this->morphTo();
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function operations()
    {
        return $this->hasMany(WarehouseBalanceOperation::class);
    }

    /**
     * Recalculate remaining weight and package count from operations and update is_finished.
     */
    public function recalculateRemaining(): void
    {
        $nonResetOutWeight = (float) $this->operations()
            ->where('source_type', '!=', 'WarehouseStockReset')
            ->sum('weight');

        $resetOp = $this->operations()
            ->where('source_type', 'WarehouseStockReset')
            ->first();

        if ($resetOp) {
            $remainingBeforeReset = max(0, (float) $this->total_weight - $nonResetOutWeight);
            $nonResetOutPkgs = (float) $this->operations()
                ->where('source_type', '!=', 'WarehouseStockReset')
                ->sum('package_count');
            $remainingPkgsBeforeReset = max(0, (float) $this->package_count - $nonResetOutPkgs);

            if (abs((float) $resetOp->weight - $remainingBeforeReset) > 0.001 || abs((float) $resetOp->package_count - $remainingPkgsBeforeReset) > 0.001) {
                $resetOp->weight = $remainingBeforeReset;
                $resetOp->package_count = $remainingPkgsBeforeReset;
                $unitP = (float) ($this->unit_price ?? 0);
                $resetOp->total_price = round($unitP * $remainingBeforeReset, 2);
                $resetOp->saveQuietly();
            }
        }

        $totalOutWeight = (float) $this->operations()->sum('weight');
        $this->remaining_weight = max(0, (float) $this->total_weight - $totalOutWeight);

        if ($this->stockable_type === 'PackageType' || (float) $this->package_count > 0) {
            $totalOutPackages = (float) $this->operations()->sum('package_count');
            $remainingPackages = max(0, (float) $this->package_count - $totalOutPackages);
            $this->is_finished = ($remainingPackages <= 0);
        } else {
            $this->is_finished = ($this->remaining_weight <= 0);
        }

        $this->save();
    }

    /**
     * Get WAP strictly from parameters/fish-management WAP Configuration.
     * Does NOT take WAP from record source.
     */
    public function getWapAttribute(): float
    {
        if ($this->stockable_type && str_contains($this->stockable_type, 'FishType')) {
            $wapConfig = FishTypeWap::getWapForFishType($this->stockable_id, $this->warehouse_id, $this->date ?? now());
            if ($wapConfig !== null) {
                return (float) $wapConfig;
            }
        }

        return 0.0;
    }

    /**
     * Get effective total price based strictly on WAP Configuration.
     */
    public function getEffectiveTotalPriceAttribute(): float
    {
        $wap = $this->wap;
        if ($wap > 0 && (float) $this->remaining_weight > 0) {
            return round($wap * (float) $this->remaining_weight, 2);
        }
        return 0.0;
    }

    public function getCleanStockableTypeAttribute(): string
    {
        $st = $this->stockable_type ?? '';
        if (str_contains($st, 'FishType')) return 'FishType';
        if (str_contains($st, 'GuanoType')) return 'GuanoType';
        if (str_contains($st, 'PackageType')) return 'PackageType';
        return class_basename($st) ?: 'Other';
    }

    public function getCleanSourceTypeAttribute(): string
    {
        $st = $this->source_type ?? '';
        if (str_contains($st, 'Production')) return 'Production';
        if (str_contains($st, 'Charge')) return 'Charge';
        if (str_contains($st, 'Transfer')) return 'WarehouseTransfer';
        if (str_contains($st, 'Inventory')) return 'Inventory';
        if (str_contains($st, 'Shipping')) return 'Shipping';
        return class_basename($st) ?: 'Manual';
    }

    public function getArticleNameAttribute(): string
    {
        $s = $this->stockable;
        if (!$s) return '#' . ($this->stockable_id ?? '?');
        return $s->name ?? $s->name_fr ?? $s->name_en ?? $s->name_es ?? ('#' . $this->stockable_id);
    }
}
