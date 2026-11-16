<?php

use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = WarehouseCategory::create([
        'name' => 'Chambres Froides',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Chambre 1',
        'capacity' => 50000,
        'warehouse_category_id' => $this->category->id,
        'temperature' => -22.0,
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Chinchard Frais',
        'name_en' => 'Horse Mackerel',
        'name_es' => 'Jurel',
        'name_in_invoice' => 'HORSE MACKEREL',
        'fish_family_id' => $this->family->id,
    ]);

    WarehouseBalance::create([
        'warehouse_id' => $this->warehouse->id,
        'stockable_type' => FishType::class,
        'stockable_id' => $this->fishType->id,
        'reception_tracking_number' => 'BR-TEST-001',
        'warehouse_operation' => 'stock_in',
        'date' => now()->format('Y-m-d'),
        'total_weight' => 10000,
        'remaining_weight' => 7500,
        'package_count' => 500,
        'unit_price' => 20.0,
        'total_price' => 150000,
        'supplier_name' => 'Armement Test',
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);
});

test('hierarchical stock endpoint returns valid structure and data', function () {
    $response = $this->getJson('/api/warehouses/hierarchical-stock?date=today&stock_type=all');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'date',
        'is_historical',
        'summary' => [
            'chambers_count',
            'species_count',
            'total_boxes',
            'total_weight',
            'total_valuation',
        ],
        'fefo_alerts',
        'emballages',
        'chambers',
    ]);

    $data = $response->json();
    expect($data['summary']['total_weight'])->toBeGreaterThan(0);
});

test('hierarchical stock historical date flag works properly', function () {
    $response = $this->getJson('/api/warehouses/hierarchical-stock?date=2026-11-01');

    $response->assertStatus(200);
    $response->assertJson([
        'date' => '2026-11-01',
        'is_historical' => true,
    ]);
});
