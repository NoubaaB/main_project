<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionCategory extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'enable_reception_tracking_number' => 'boolean',
        'enable_tracking_onp_numbers' => 'boolean',
        'enable_production_tracking_numbers' => 'boolean',
        'enable_fishery_id' => 'boolean',
        'enable_ship_id' => 'boolean',
        'enable_origin_id' => 'boolean',
        'enable_truck_id' => 'boolean',
        'enable_supplier_process_id' => 'boolean',
    ];

    public function productions()
    {
        return $this->hasMany(Production::class);
    }
}
