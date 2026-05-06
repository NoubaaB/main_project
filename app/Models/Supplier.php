<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    function profit_and_loss_account() : MorphOne {
        return $this->morphOne(ProfitAndLossAccount::class, 'accountable');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
