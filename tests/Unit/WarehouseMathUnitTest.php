<?php

use App\Http\Controllers\WarehouseController;
use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\FishTypeWap;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cleanLotNumber correctly parses JSON arrays, strings and messy tokens', function () {
    expect(WarehouseController::cleanLotNumber('["511", "514"]'))->toBe('511, 514');
    expect(WarehouseController::cleanLotNumber('["715"]'))->toBe('715');
    expect(WarehouseController::cleanLotNumber('511'))->toBe('511');
    expect(WarehouseController::cleanLotNumber('[""]'))->toBe('-');
    expect(WarehouseController::cleanLotNumber('[]'))->toBe('-');
    expect(WarehouseController::cleanLotNumber(''))->toBe('-');
    expect(WarehouseController::cleanLotNumber(null))->toBe('-');
});

test('FishTypeWap caching and lookup matches valid date periods', function () {
    $wh = Warehouse::create(['name' => 'Chamber WAP Test']);
    $fam = FishFamily::create(['name_fr' => 'Pela', 'name_en' => 'Pela', 'name_es' => 'Pela']);
    $fish = FishType::create([
        'name_fr' => 'Sardine WAP',
        'name_en' => 'Sardine WAP',
        'name_es' => 'Sardine WAP',
        'name_in_invoice' => 'SARDINE WAP',
        'fish_family_id' => $fam->id,
    ]);

    FishTypeWap::clearCachedRules();

    // No rules yet
    expect(FishTypeWap::getWapForFishType($fish->id, $wh->id, '2026-05-15'))->toBeNull();

    // Add rule
    $wapRule = FishTypeWap::create([
        'fish_type_id' => $fish->id,
        'warehouse_id' => $wh->id,
        'date_start' => '2026-01-01',
        'date_end' => '2026-12-31',
        'wap' => 18.75,
    ]);

    // Matching period
    expect(FishTypeWap::getWapForFishType($fish->id, $wh->id, '2026-06-01'))->toBe(18.75);

    // Outside period
    expect(FishTypeWap::getWapForFishType($fish->id, $wh->id, '2025-12-31'))->toBeNull();
});

test('WarehouseBalance computes correct effective total price based on WAP or unit price', function () {
    $wh = Warehouse::create(['name' => 'Chamber Price Test']);
    $fam = FishFamily::create(['name_fr' => 'Pela', 'name_en' => 'Pela', 'name_es' => 'Pela']);
    $fish = FishType::create([
        'name_fr' => 'Sardine Price',
        'name_en' => 'Sardine Price',
        'name_es' => 'Sardine Price',
        'name_in_invoice' => 'SARDINE PR',
        'fish_family_id' => $fam->id,
    ]);

    // Create WAP rule
    FishTypeWap::create([
        'fish_type_id' => $fish->id,
        'warehouse_id' => $wh->id,
        'date_start' => '2026-01-01',
        'date_end' => null,
        'wap' => 20.0,
    ]);

    $wb = WarehouseBalance::create([
        'warehouse_id' => $wh->id,
        'stockable_type' => FishType::class,
        'stockable_id' => $fish->id,
        'reception_tracking_number' => '["511"]',
        'warehouse_operation' => 'stock_in',
        'date' => '2026-04-10',
        'total_weight' => 1000,
        'remaining_weight' => 500,
        'unit_price' => 15.0,
        'total_price' => 15000,
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);

    expect($wb->lot_number)->toBe('511');
    expect($wb->clean_stockable_type)->toBe('FishType');
    expect($wb->wap)->toBe(20.0);
    // Effective total price = WAP * remaining_weight = 20.0 * 500 = 10000.0
    expect($wb->effective_total_price)->toBe(10000.0);
});

