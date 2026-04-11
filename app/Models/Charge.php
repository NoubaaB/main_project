<?php

namespace App\Models;

use App\Models\ChargeAuditLog;
use App\Models\ChargeCategory;
use App\Models\Fishery;
use App\Models\NonWarehouseStockableOperation;
use App\Models\Origin;
use App\Models\Ship;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Truck;
use App\Models\User;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Charge extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'];

    protected $guarded = [];

    protected $hidden = ['trucks'];

    protected $appends = ['supplier_ids','truck_ids', 'supplier_invoice_tracking_number', 'created_by_name', 'audit_logs_count'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'note' => 'string',
        'total_price'  => 'float',
        'total_weight' => 'float',
        'total_package_count' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // $model->calc();
        });

        static::saving(function ($model) {
        });

        static::deleted(function ($model) {
        });
    }

    function non_warehouse_stockable_operations() : HasMany {
        return $this->hasMany(NonWarehouseStockableOperation::class);
    }

    function charge_category() : BelongsTo {
        return $this->belongsTo(ChargeCategory::class, 'charge_category_id'); 
    }

    function warehouse_stockable_tracking_number_operations() : MorphMany {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'sourceable');
    }

    function suppliers() : BelongsToMany 
    {
        return $this->belongsToMany(Supplier::class, "charge_suppliers", "charge_id", "supplier_id");
    }

    function fishery() : BelongsTo {
        return $this->belongsTo(Fishery::class);
    }

    function ships(): BelongsToMany
    {
        return $this->belongsToMany(Ship::class, "charge_ships", "charge_id", "ship_id")
            ->withPivot('charge_id', 'ship_id');
    }

    function trucks() : BelongsToMany {
        return $this->belongsToMany(Truck::class,"truck_charge");
    }

    function origin() : BelongsTo {
        return $this->belongsTo(Origin::class, 'origin_id'); 
    }

    function supplier_process() : BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_process_id'); 
    }

    function creator() : BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    function updater() : BelongsTo {
        return $this->belongsTo(User::class, 'updated_by');
    }

    function audit_logs() : HasMany {
        return $this->hasMany(ChargeAuditLog::class)->orderByDesc('created_at');
    }

    function supplier_invoice() : BelongsTo {
        return $this->belongsTo(SupplierInvoice::class, "invoice_id");
    }

    protected function supplierIds(): Attribute
    {
        return Attribute::get(fn() => $this->suppliers->pluck('id')->toArray());
    }

    protected function truckIds(): Attribute
    {
        return Attribute::get(fn() => $this->trucks->pluck('id')->toArray());
    }

    protected function supplierInvoiceTrackingNumber(): Attribute
    {
        $supplier_invoice_tracking_number = DB::table('supplier_invoices')->where("id",$this->invoice_id)->get("tracking_numbers")->first();
        if($supplier_invoice_tracking_number) {
            return Attribute::get(fn() => json_decode($supplier_invoice_tracking_number->tracking_numbers, true));
        }
        return Attribute::get(fn() => []);
    }

    protected function createdByName(): Attribute
    {
        return Attribute::get(fn() => $this->creator?->name ?? null);
    }

    protected function auditLogsCount(): Attribute
    {
        return Attribute::get(fn() => $this->audit_logs()->count());
    }

    //private functions

    function calc() : void {
        $total_weight = 0.00;
        $total_price = 0.00;
        $total_package_count = 0.00;
        foreach ($this->warehouse_stockable_tracking_number_operations as $warehouse_stockable_tracking_number_operation) {
            $total_weight += $warehouse_stockable_tracking_number_operation->total_weight;
            $total_price += $warehouse_stockable_tracking_number_operation->total_price;
            $total_package_count += $warehouse_stockable_tracking_number_operation->total_package_count;
        }

        foreach ($this->non_warehouse_stockable_operations as $non_warehouse_stockable_operation) {
            $total_weight += $non_warehouse_stockable_operation->total_weight;
            $total_price += $non_warehouse_stockable_operation->total_price;
            $total_package_count += $non_warehouse_stockable_operation->total_package_count;
        }

        $t = [
            "total_weight" => $total_weight,
            "total_price" => $total_price,
            "total_package_count" => $total_package_count,
        ];
        $this->update($t);
    }
}
