<?php

use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = WarehouseTransferCategory::create([
        'name' => 'Transfert Inter-Chambres Standard',
    ]);

    $this->sourceWarehouse = Warehouse::create([
        'name' => 'Chambre Depart A',
    ]);

    $this->destWarehouse = Warehouse::create([
        'name' => 'Chambre Arrivee B',
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Sardine Transfert',
        'name_en' => 'Sardine Transfer',
        'name_es' => 'Sardina Transfer',
        'name_in_invoice' => 'SARDINE TR',
        'fish_family_id' => $this->family->id,
    ]);

    $this->balance = WarehouseBalance::create([
        'warehouse_id' => $this->sourceWarehouse->id,
        'stockable_type' => FishType::class,
        'stockable_id' => $this->fishType->id,
        'reception_tracking_number' => '511',
        'warehouse_operation' => 'stock_in',
        'date' => now()->subDays(2)->format('Y-m-d'),
        'total_weight' => 5000,
        'remaining_weight' => 5000,
        'package_count' => 200,
        'unit_price' => 10.0,
        'total_price' => 50000,
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);
});

test('can create a warehouse transfer between chambers', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'transfer_tracking_number' => ['TR-2026-001'],
        'details' => [
            [
                'warehouse_transfer_category_id' => $this->category->id,
                'stockable_type' => FishType::class,
                'stockable_id' => $this->fishType->id,
                'weight' => 1500,
                'source_warehouse_id' => $this->sourceWarehouse->id,
                'source_tracking_number' => '511',
                'destination_warehouse_id' => $this->destWarehouse->id,
                'nbr_box' => 60,
            ]
        ]
    ];

    $response = $this->postJson('/api/warehouse-transfer', $payload);

    $response->assertStatus(201);
    expect($response->json('warehouse_transfer.id'))->not->toBeNull();
    expect((float)$response->json('warehouse_transfer.total_weight'))->toBe(1500.0);

    // Source balance should have decreased
    $this->balance->refresh();
    expect((float)$this->balance->remaining_weight)->toBe(3500.0);

    // Destination balance should have been created
    $destBalance = WarehouseBalance::where('warehouse_id', $this->destWarehouse->id)
        ->where('warehouse_operation', 'stock_in')
        ->first();
    expect($destBalance)->not->toBeNull();
    expect((float)$destBalance->remaining_weight)->toBe(1500.0);
});

test('can show a warehouse transfer', function () {
    $tr = WarehouseTransfer::create([
        'date' => now()->format('Y-m-d'),
        'total_weight' => 1000,
    ]);

    $response = $this->getJson("/api/warehouse-transfer/{$tr->id}");
    $response->assertStatus(200);
    expect($response->json('warehouse_transfer.id'))->toBe($tr->id);
});

test('can delete a warehouse transfer and reverse stock operations', function () {
    $tr = WarehouseTransfer::create([
        'date' => now()->format('Y-m-d'),
        'total_weight' => 1000,
    ]);

    $response = $this->deleteJson('/api/warehouse-transfer', ['ids' => [$tr->id]]);
    $response->assertStatus(200);

    expect(WarehouseTransfer::find($tr->id))->toBeNull();
});
