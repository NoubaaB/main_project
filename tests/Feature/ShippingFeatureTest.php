<?php

use App\Models\Client;
use App\Models\DetailShippingCategory;
use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Shipping;
use App\Models\ShippingCategory;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = ShippingCategory::create([
        'name' => 'Expedition Export',
    ]);

    $this->detailCategory = DetailShippingCategory::create([
        'name' => 'Vente Export Congelé',
        'shipping_category_id' => $this->category->id,
    ]);

    $this->client = Client::create([
        'name' => 'Pesca Espana SA',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Chamber 3 Expéditions',
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Sardine Congelée',
        'name_en' => 'Frozen Sardine',
        'name_es' => 'Sardina Congelada',
        'name_in_invoice' => 'FROZEN SARDINE',
        'fish_family_id' => $this->family->id,
    ]);

    // Initial stock in warehouse
    $this->initialBalance = WarehouseBalance::create([
        'warehouse_id' => $this->warehouse->id,
        'stockable_type' => FishType::class,
        'stockable_id' => $this->fishType->id,
        'reception_tracking_number' => '511',
        'warehouse_operation' => 'stock_in',
        'date' => now()->subDays(5)->format('Y-m-d'),
        'total_weight' => 5000,
        'remaining_weight' => 5000,
        'package_count' => 200,
        'unit_price' => 15.0,
        'total_price' => 75000,
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);
});

test('can create a shipping voucher and register stock out movement', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'shipping_category_id' => $this->category->id,
        'client_id' => $this->client->id,
        'direction' => 'export',
        'shipping_tracking_number' => ['EXP-2026-001'],
        'details' => [
            [
                'detail_shipping_category_id' => $this->detailCategory->id,
                'stockable_type' => FishType::class,
                'stockable_id' => $this->fishType->id,
                'weight' => 2000,
                'unit_price' => 18.0,
                'total_price' => 36000,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_operation' => 'stock_out',
                'nbr_box' => 80,
            ]
        ]
    ];

    $response = $this->postJson('/api/shipping', $payload);

    $response->assertStatus(201);
    expect($response->json('shipping.id'))->not->toBeNull();
    expect((float)$response->json('shipping.total_weight'))->toBe(2000.0);

    // Verify stock remaining weight decreased
    $this->initialBalance->refresh();
    expect((float)$this->initialBalance->remaining_weight)->toBe(3000.0);

    // Verify WarehouseBalanceOperation was logged
    $operation = WarehouseBalanceOperation::where('warehouse_balance_id', $this->initialBalance->id)->first();
    expect($operation)->not->toBeNull();
    expect((float)$operation->weight)->toBe(2000.0);
});

test('can show a shipping voucher', function () {
    $shipping = Shipping::create([
        'date' => now()->format('Y-m-d'),
        'shipping_category_id' => $this->category->id,
        'client_id' => $this->client->id,
        'total_weight' => 1000,
        'total_price' => 18000,
    ]);

    $response = $this->getJson("/api/shipping/{$shipping->id}");
    $response->assertStatus(200);
    expect($response->json('shipping.id'))->toBe($shipping->id);
});

test('can delete a shipping voucher without linked invoice', function () {
    $shipping = Shipping::create([
        'date' => now()->format('Y-m-d'),
        'shipping_category_id' => $this->category->id,
        'client_id' => $this->client->id,
        'total_weight' => 1000,
        'total_price' => 18000,
    ]);

    $response = $this->deleteJson('/api/shipping', ['ids' => [$shipping->id]]);
    $response->assertStatus(200);

    expect(Shipping::find($shipping->id))->toBeNull();
});

