<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'charge_id', 'detail_charge_category_id', 'article_description',
        'stockable_type', 'stockable_id', 'weight', 'unit_price', 'total_price',
        'weight_invoice', 'package_type_id', 'nbr_box', 'warehouse_id', 'warehouse_operation', 'truck_id', 'supplier_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'weight_invoice' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'nbr_box' => 'decimal:2',
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function detailChargeCategory()
    {
        return $this->belongsTo(DetailChargeCategory::class);
    }

    public function stockable()
    {
        return $this->morphTo();
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    protected static function booted()
    {
        static::saving(function (DetailCharge $dc) {
            // Auto-calculate missing total_price or unit_price
            if ($dc->weight > 0 && $dc->unit_price > 0 && (!$dc->total_price || (float) $dc->total_price == 0)) {
                $dc->total_price = round((float) $dc->weight * (float) $dc->unit_price, 2);
            } elseif ($dc->nbr_box > 0 && $dc->unit_price > 0 && (!$dc->weight || (float) $dc->weight == 0) && (!$dc->total_price || (float) $dc->total_price == 0)) {
                $dc->total_price = round((float) $dc->nbr_box * (float) $dc->unit_price, 2);
            } elseif ($dc->weight > 0 && $dc->total_price > 0 && (!$dc->unit_price || (float) $dc->unit_price == 0)) {
                $dc->unit_price = round((float) $dc->total_price / (float) $dc->weight, 2);
            }

            // Default weight_invoice to weight if weight is given but weight_invoice is null
            if (($dc->weight_invoice === null || $dc->weight_invoice === '') && $dc->weight > 0) {
                $dc->weight_invoice = $dc->weight;
            }
        });

        static::saved(function (DetailCharge $dc) {
            if ($dc->charge_id) {
                $charge = Charge::find($dc->charge_id);
                $charge?->recalculateTotals();
            }
        });

        static::deleted(function (DetailCharge $dc) {
            if ($dc->charge_id) {
                $charge = Charge::find($dc->charge_id);
                $charge?->recalculateTotals();
            }
        });
    }
}
