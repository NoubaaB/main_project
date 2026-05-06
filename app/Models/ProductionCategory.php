<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductionCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'name_fr', 'name_en', 'name_es', 'description',
        'enable_reception_tracking_number', 'enable_tracking_onp_numbers',
        'enable_production_tracking_number', 'enable_production_type',
        'enable_fishery_id', 'enable_ship_id', 'enable_origin_id', 'enable_truck_id',
        'enable_suppliers', 'enable_clients', 'enable_invoices',
        'enable_quality_control',
    ];

    protected $casts = [
        'enable_reception_tracking_number' => 'boolean',
        'enable_tracking_onp_numbers' => 'boolean',
        'enable_production_tracking_number' => 'boolean',
        'enable_production_type' => 'boolean',
        'enable_fishery_id' => 'boolean',
        'enable_ship_id' => 'boolean',
        'enable_origin_id' => 'boolean',
        'enable_truck_id' => 'boolean',
        'enable_suppliers' => 'boolean',
        'enable_clients' => 'boolean',
        'enable_invoices' => 'boolean',
        'enable_quality_control' => 'boolean',
    ];

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    public function detailProductionCategories()
    {
        return $this->hasMany(DetailProductionCategory::class);
    }
}
