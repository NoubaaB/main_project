<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageSecondaryConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'package_type_id',
        'date_start',
        'date_end',
        'caisses_qte',
        'caisses_price',
        'cellophane_qte',
        'cellophane_price',
        'plastique_qte',
        'plastique_price',
        'strapex_qte',
        'strapex_price',
        'palletes_qte',
        'palletes_price',
        'scotch_qte',
        'scotch_price',
        'unit_cost',
    ];

    protected $casts = [
        'date_start' => 'date:Y-m-d',
        'date_end' => 'date:Y-m-d',
        'caisses_qte' => 'decimal:4',
        'caisses_price' => 'decimal:4',
        'cellophane_qte' => 'decimal:4',
        'cellophane_price' => 'decimal:4',
        'plastique_qte' => 'decimal:4',
        'plastique_price' => 'decimal:4',
        'strapex_qte' => 'decimal:4',
        'strapex_price' => 'decimal:4',
        'palletes_qte' => 'decimal:4',
        'palletes_price' => 'decimal:4',
        'scotch_qte' => 'decimal:4',
        'scotch_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    /**
     * Compute unit cost for 1 package from the 6 components
     */
    public function computeUnitCost(): float
    {
        $cost = (float) $this->caisses_qte * (float) $this->caisses_price
            + (float) $this->cellophane_qte * (float) $this->cellophane_price
            + (float) $this->plastique_qte * (float) $this->plastique_price
            + (float) $this->strapex_qte * (float) $this->strapex_price
            + (float) $this->palletes_qte * (float) $this->palletes_price
            + (float) $this->scotch_qte * (float) $this->scotch_price;

        return round($cost, 4);
    }

    /**
     * Find active config for a given package type and date
     */
    public static function getConfigForPackageAndDate($packageTypeId, $date): ?self
    {
        if (!$packageTypeId || !$date) {
            return null;
        }

        $dateStr = is_string($date) ? substr($date, 0, 10) : $date->format('Y-m-d');

        return static::where('package_type_id', $packageTypeId)
            ->where('date_start', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('date_end')
                  ->orWhere('date_end', '>=', $dateStr);
            })
            ->orderBy('date_start', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }
}
