<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageTypeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_type_id',
        'target_package_type_id',
        'multiplier',
    ];

    protected $casts = [
        'multiplier' => 'decimal:4',
    ];

    public function packageType()
    {
        return $this->belongsTo(PackageType::class, 'package_type_id');
    }

    public function targetPackageType()
    {
        return $this->belongsTo(PackageType::class, 'target_package_type_id');
    }
}
