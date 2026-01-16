<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierInvoice extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierInvoiceFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        "tracking_numbers"=>"array",
        "supplier_ids"=>"array"
    ];

    protected $appends = ['charge_ids'];

    function charges() : HasMany {
        return $this->hasMany(Charge::class,"invoice_id");
    }

    protected function chargeIds(): Attribute
    {
        return Attribute::get(fn() => $this->charges->pluck('id')->toArray());
    }
}
