<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'total_price',
        'total_weight',
        'total_secondary_packaging_cost',
        'fresh_reception_weight',
        'frozen_production_weight',
        'guano_production_weight',
        'equilibrium',
        'equilibrium_status',
        'production_category_id',
        'reception_tracking_number',
        'tracking_onp_numbers',
        'production_tracking_number',
        'production_type',
        'fishery_id',
        'ship_id',
        'origin_id',
        'charge_id',
        'supplier_id',
        'client_id',
        'allow_invoice',
        'supplier_invoice_id',
        'client_invoice_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'total_secondary_packaging_cost' => 'decimal:2',
        'fresh_reception_weight' => 'decimal:2',
        'frozen_production_weight' => 'decimal:2',
        'guano_production_weight' => 'decimal:2',
        'equilibrium' => 'decimal:2',
        'reception_tracking_number' => 'array',
        'tracking_onp_numbers' => 'array',
        'production_tracking_number' => 'array',
        'allow_invoice' => 'boolean',
    ];

    public function productionCategory()
    {
        return $this->belongsTo(ProductionCategory::class);
    }

    public function detailProductions()
    {
        return $this->hasMany(DetailProduction::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function fishery()
    {
        return $this->belongsTo(Fishery::class);
    }

    public function ship()
    {
        return $this->belongsTo(Ship::class);
    }

    public function origin()
    {
        return $this->belongsTo(Origin::class);
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class);
    }

    public function qualityControls()
    {
        return $this->hasMany(QualityControl::class);
    }
}
