<?php

use App\Models\DetailInventoryCategory;
use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->detailCategory = DetailInventoryCategory::create([
        'name' => 'Ajustement Inventaire Annuel',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Chambre 4 Stockage',
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Anchois Frais',
        'name_en' => 'Fresh Anchovy',
        'name_es' => 'Boqueron',
        'name_in_invoice' => 'ANCHOVY INVOICE',
        'fish_family_id' => $this->family->id,
    ]);
});

test('can create an inventory stock-in calibration record', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'inventory_tracking_number' => ['INV-2026-001'],
        'details' => [
            [
                'detail_inventory_category_id' => $this->detailCategory->id,
                'stockable_type' => FishType::class,
                'stockable_id' => $this->fishType->id,
                'weight' => 850,
                'unit_price' => 15.0,
                'total_price' => 12750,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_operation' => 'stock_in',
                'nbr_box' => 35,
            ]
        ]
    ];

    $response = $this->postJson('/api/inventory', $payload);

    $response->assertStatus(201);
    expect($response->json('inventory.id'))->not->toBeNull();

    // Verify balance was created
    $balance = WarehouseBalance::where('source_type', 'DetailInventory')
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance)->not->toBeNull();
    expect((float)$balance->remaining_weight)->toBe(850.0);
});

test('can show an inventory record', function () {
    $inv = Inventory::create([
        'date' => now()->format('Y-m-d'),
        'total_weight' => 500,
        'total_price' => 7500,
    ]);

    $response = $this->getJson("/api/inventory/{$inv->id}");
    $response->assertStatus(200);
    expect($response->json('inventory.id'))->toBe($inv->id);
});

test('can delete an inventory record', function () {
    $inv = Inventory::create([
        'date' => now()->format('Y-m-d'),
        'total_weight' => 500,
        'total_price' => 7500,
    ]);

    $response = $this->deleteJson('/api/inventory', ['ids' => [$inv->id]]);
    $response->assertStatus(200);

    expect(Inventory::find($inv->id))->toBeNull();
});

