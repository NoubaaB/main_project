<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ShippingCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'name_fr', 'name_en', 'name_es', 'description',
        'enable_shipping_tracking_number', 'enable_tracking_onp_numbers',
        'enable_fishery_id', 'enable_ship_id',
        'enable_origin_id', 'enable_truck_id',
        'enable_clients', 'enable_direction', 'enable_invoices',
    ];

    protected $casts = [
        'enable_shipping_tracking_number' => 'boolean',
        'enable_tracking_onp_numbers' => 'boolean',
        'enable_fishery_id' => 'boolean',
        'enable_ship_id' => 'boolean',
        'enable_origin_id' => 'boolean',
        'enable_truck_id' => 'boolean',
        'enable_clients' => 'boolean',
        'enable_direction' => 'boolean',
        'enable_invoices' => 'boolean',
    ];

    public function shippings()
    {
        return $this->hasMany(Shipping::class);
    }

    public function detailShippingCategories()
    {
        return $this->hasMany(DetailShippingCategory::class);
    }
}
