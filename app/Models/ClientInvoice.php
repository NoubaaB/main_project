<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'tracking_numbers' => 'array',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:2',
    ];

    protected $appends = ['shipping_ids', 'production_ids'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shippings(): HasMany
    {
        return $this->hasMany(Shipping::class, 'invoice_id');
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class, 'client_invoice_id');
    }

    protected function shippingIds(): Attribute
    {
        return Attribute::get(fn() => $this->shippings->pluck('id')->toArray());
    }

    protected function productionIds(): Attribute
    {
        return Attribute::get(fn() => $this->productions->pluck('id')->toArray());
    }
}
