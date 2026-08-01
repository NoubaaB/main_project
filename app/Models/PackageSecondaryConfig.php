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
        'components',
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
        'components' => 'array',
    ];

    protected $appends = ['resolved_components'];

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    /**
     * Get resolved list of components (with defaults if components column is empty)
     */
    public function getResolvedComponentsAttribute(): array
    {
        if (is_array($this->components) && count($this->components) > 0) {
            return array_map(function ($c) {
                return [
                    'key' => $c['key'] ?? ('comp_' . uniqid()),
                    'name' => $c['name'] ?? '',
                    'unit' => $c['unit'] ?? 'U',
                    'qte' => (float) ($c['qte'] ?? 0),
                    'price' => (float) ($c['price'] ?? 0),
                    'is_custom' => (bool) ($c['is_custom'] ?? false),
                ];
            }, $this->components);
        }

        return [
            ['key' => 'caisses', 'name' => 'Caisses', 'unit' => 'U', 'qte' => (float) $this->caisses_qte, 'price' => (float) $this->caisses_price, 'is_custom' => false],
            ['key' => 'cellophane', 'name' => 'Cellophane', 'unit' => 'Kg', 'qte' => (float) $this->cellophane_qte, 'price' => (float) $this->cellophane_price, 'is_custom' => false],
            ['key' => 'plastique', 'name' => 'Plastique', 'unit' => 'Kg', 'qte' => (float) $this->plastique_qte, 'price' => (float) $this->plastique_price, 'is_custom' => false],
            ['key' => 'strapex', 'name' => 'Strapex', 'unit' => 'm', 'qte' => (float) $this->strapex_qte, 'price' => (float) $this->strapex_price, 'is_custom' => false],
            ['key' => 'palletes', 'name' => 'Palletes', 'unit' => 'U', 'qte' => (float) $this->palletes_qte, 'price' => (float) $this->palletes_price, 'is_custom' => false],
            ['key' => 'scotch', 'name' => 'Scotch', 'unit' => 'Rouleau', 'qte' => (float) $this->scotch_qte, 'price' => (float) $this->scotch_price, 'is_custom' => false],
        ];
    }

    /**
     * Compute unit cost for 1 package from components
     */
    public function computeUnitCost(): float
    {
        $comps = $this->resolved_components;
        $cost = 0;
        foreach ($comps as $c) {
            $cost += ((float) ($c['qte'] ?? 0)) * ((float) ($c['price'] ?? 0));
        }

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
