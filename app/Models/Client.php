<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function shippings(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function clientInvoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }

    public function clientPayments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function profit_and_loss_account(): MorphOne
    {
        return $this->morphOne(ProfitAndLossAccount::class, 'accountable');
    }
}
