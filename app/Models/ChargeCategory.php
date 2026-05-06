<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ChargeCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'name_fr', 'name_en', 'name_es', 'description',
        'enable_reception_tracking_number', 'enable_tracking_onp_numbers',
        'enable_fishery_id', 'enable_ship_id', 'enable_origin_id',
        'enable_truck_id', 'enable_suppliers', 'is_production', 'enable_invoices',
    ];

    protected $casts = [
        'enable_reception_tracking_number' => 'boolean',
        'enable_tracking_onp_numbers' => 'boolean',
        'enable_fishery_id' => 'boolean',
        'enable_ship_id' => 'boolean',
        'enable_origin_id' => 'boolean',
        'enable_truck_id' => 'boolean',
        'enable_suppliers' => 'boolean',
        'is_production' => 'boolean',
        'enable_invoices' => 'boolean',
    ];

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    public function detailChargeCategories()
    {
        return $this->hasMany(DetailChargeCategory::class);
    }
}
