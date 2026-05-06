<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'total_price',
        'total_weight',
        'charge_category_id',
        'reception_tracking_number',
        'tracking_onp_numbers',
        'fishery_id',
        'ship_id',
        'origin_id',
        'description',
        'supplier_id',
        'allow_invoice',
        'invoice_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'reception_tracking_number' => 'array',
        'tracking_onp_numbers' => 'array',
        'allow_invoice' => 'boolean',
    ];

    public function chargeCategory()
    {
        return $this->belongsTo(ChargeCategory::class);
    }

    public function detailCharges()
    {
        return $this->hasMany(DetailCharge::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'charge_truck');
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

    public function production()
    {
        return $this->hasOne(Production::class);
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }
}
