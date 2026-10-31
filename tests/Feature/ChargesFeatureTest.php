<?php

use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\DetailChargeCategory;
use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = ChargeCategory::create([
        'name' => 'Reception Fish Category',
        'is_allow_negative' => false,
    ]);

    $this->detailCategory = DetailChargeCategory::create([
        'name' => 'Matiere Premiere',
        'charge_category_id' => $this->category->id,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Atlantic Fishing SARL',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Chamber 1 Congelation',
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Sardine Fraiche',
        'name_en' => 'Fresh Sardine',
        'name_es' => 'Sardina Fresca',
        'name_in_invoice' => 'SARDINE INVOICE',
        'fish_family_id' => $this->family->id,
    ]);
});

test('can create a charge with details and generate warehouse balance', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'charge_category_id' => $this->category->id,
        'supplier_id' => $this->supplier->id,
        'reception_tracking_number' => ['511'],
        'details' => [
            [
                'detail_charge_category_id' => $this->detailCategory->id,
                'stockable_type' => FishType::class,
                'stockable_id' => $this->fishType->id,
                'weight' => 1500,
                'unit_price' => 10.5,
                'total_price' => 15750,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_operation' => 'stock_in',
                'nbr_box' => 50,
            ]
        ]
    ];

    $response = $this->postJson('/api/charge', $payload);

    $response->assertStatus(201);
    expect($response->json('charge.id'))->not->toBeNull();
    expect((float)$response->json('charge.total_weight'))->toBe(1500.0);

    // Verify WarehouseBalance was created
    $balance = WarehouseBalance::where('source_type', 'DetailCharge')
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance)->not->toBeNull();
    expect((float)$balance->total_weight)->toBe(1500.0);
    expect((float)$balance->remaining_weight)->toBe(1500.0);
});

test('can show a created charge', function () {
    $charge = Charge::create([
        'date' => now()->format('Y-m-d'),
        'charge_category_id' => $this->category->id,
        'supplier_id' => $this->supplier->id,
        'total_price' => 5000,
        'total_weight' => 500,
    ]);

    $response = $this->getJson("/api/charge/{$charge->id}");
    $response->assertStatus(200);
    expect($response->json('charge.id'))->toBe($charge->id);
});

test('can delete a charge without linked invoice', function () {
    $charge = Charge::create([
        'date' => now()->format('Y-m-d'),
        'charge_category_id' => $this->category->id,
        'supplier_id' => $this->supplier->id,
        'total_price' => 2000,
        'total_weight' => 200,
    ]);

    $response = $this->deleteJson("/api/charge", ['ids' => [$charge->id]]);
    $response->assertStatus(200);

    expect(Charge::find($charge->id))->toBeNull();
});
