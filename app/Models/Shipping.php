<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'total_price',
        'total_weight',
        'shipping_category_id',
        'shipping_tracking_number',
        'tracking_onp_numbers',
        'description',
        'fishery_id',
        'ship_id',
        'origin_id',
        'direction',
        'client_id',
        'allow_invoice',
        'invoice_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'shipping_tracking_number' => 'array',
        'tracking_onp_numbers' => 'array',
        'allow_invoice' => 'boolean',
    ];

    public function shippingCategory()
    {
        return $this->belongsTo(ShippingCategory::class);
    }

    public function detailShippings()
    {
        return $this->hasMany(DetailShipping::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'shipping_truck');
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

    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class, 'invoice_id');
    }
}
