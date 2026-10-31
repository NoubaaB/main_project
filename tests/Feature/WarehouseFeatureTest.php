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
        'name' => 'Chambre Alpha',
        'capacity' => 50000,
        'warehouse_category_id' => $this->category->id,
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
        'reception_tracking_number' => '511',
        'warehouse_operation' => 'stock_in',
        'date' => now()->format('Y-m-d'),
        'total_weight' => 10000,
        'remaining_weight' => 7500,
        'package_count' => 500,
        'unit_price' => 20.0,
        'total_price' => 200000,
        'supplier_name' => 'Armement Nord',
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);
});

test('can list warehouses', function () {
    $response = $this->getJson('/api/warehouses');
    $response->assertStatus(200);
    expect($response->json('warehouses'))->toHaveCount(1);
    expect($response->json('warehouses.0.name'))->toBe('Chambre Alpha');
});

test('can create a warehouse', function () {
    $payload = [
        'name' => 'Chambre Beta',
        'capacity' => 75000,
        'warehouse_category_id' => $this->category->id,
        'has_sejour' => false,
    ];

    $response = $this->postJson('/api/warehouses', $payload);
    $response->assertStatus(201);
    expect($response->json('warehouse.name'))->toBe('Chambre Beta');
});

test('can get chambers overview with stock stats', function () {
    $response = $this->getJson('/api/warehouses/chambers-overview');
    $response->assertStatus(200);
    expect($response->json('chambers'))->toHaveCount(1);
    expect((float)$response->json('chambers.0.total_weight'))->toBe(7500.0);
    expect($response->json('chambers.0.active_lots'))->toBe(1);
});

test('can get species overview with aggregation', function () {
    $response = $this->getJson('/api/warehouses/species-overview');
    $response->assertStatus(200);
    expect($response->json('species'))->toHaveCount(1);
    expect((float)$response->json('species.0.total_weight'))->toBe(7500.0);
});

test('can get stock par lot paginated with KPIs', function () {
    $response = $this->getJson('/api/warehouses/stock-par-lot?status=in_stock');
    $response->assertStatus(200);
    expect($response->json('summary.in_stock_lots'))->toBe(1);
    expect((float)$response->json('summary.total_remaining_weight'))->toBe(7500.0);
    expect($response->json('lots'))->toHaveCount(1);
});

test('can get warehouse analytics', function () {
    $response = $this->getJson('/api/warehouses/analytics');
    $response->assertStatus(200);
    expect($response->json('summary'))->not->toBeNull();
    expect($response->json('aging'))->not->toBeNull();
});
