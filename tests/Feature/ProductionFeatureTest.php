<?php

use App\Models\DetailProductionCategory;
use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Production;
use App\Models\ProductionCategory;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = ProductionCategory::create([
        'name' => 'Congelation Standard',
    ]);

    $this->detailCategory = DetailProductionCategory::create([
        'name' => 'Produit Fini Congelé',
        'production_category_id' => $this->category->id,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Maroc Peche',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Tunnel Congelation 2',
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Maquereau Entier',
        'name_en' => 'Whole Mackerel',
        'name_es' => 'Caballa Entera',
        'name_in_invoice' => 'MACKEREL INVOICE',
        'fish_family_id' => $this->family->id,
    ]);
});

test('can create a production batch with finished goods and update warehouse stock', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'production_category_id' => $this->category->id,
        'supplier_id' => $this->supplier->id,
        'production_type' => 'standard',
        'reception_tracking_number' => ['715'],
        'production_tracking_number' => ['PROD-715-1'],
        'details' => [
            [
                'detail_production_category_id' => $this->detailCategory->id,
                'stockable_type' => FishType::class,
                'stockable_id' => $this->fishType->id,
                'weight' => 2400,
                'unit_price' => 12.0,
                'total_price' => 28800,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_operation' => 'stock_in',
                'nbr_box' => 120,
            ]
        ]
    ];

    $response = $this->postJson('/api/productions', $payload);

    $response->assertStatus(201);
    expect($response->json('production.id'))->not->toBeNull();
    expect((float)$response->json('production.total_weight'))->toBe(2400.0);

    // Verify balance was created
    $balance = WarehouseBalance::where('source_type', 'DetailProduction')
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance)->not->toBeNull();
    expect((float)$balance->total_weight)->toBe(2400.0);
    expect((float)$balance->remaining_weight)->toBe(2400.0);
});

test('can show a production record', function () {
    $prod = Production::create([
        'date' => now()->format('Y-m-d'),
        'production_category_id' => $this->category->id,
        'total_weight' => 1000,
        'total_price' => 12000,
    ]);

    $response = $this->getJson("/api/productions/{$prod->id}");
    $response->assertStatus(200);
    expect($response->json('production.id'))->toBe($prod->id);
});

test('can delete a production record', function () {
    $prod = Production::create([
        'date' => now()->format('Y-m-d'),
        'production_category_id' => $this->category->id,
        'total_weight' => 1000,
        'total_price' => 12000,
    ]);

    $response = $this->deleteJson('/api/productions', ['ids' => [$prod->id]]);
    $response->assertStatus(200);

    expect(Production::find($prod->id))->toBeNull();
});

