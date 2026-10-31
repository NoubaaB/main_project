<?php

use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\DetailChargeCategory;
use App\Models\DetailProductionCategory;
use App\Models\DetailShippingCategory;
use App\Models\Production;
use App\Models\ProductionCategory;
use App\Models\Shipping;
use App\Models\ShippingCategory;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\PlafondService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);
});

test('can create, list, update, and delete a client with plafond settings', function () {
    $payload = [
        'name' => 'Atlantic Export Co',
        'ice' => '001234567890001',
        'phone' => '+212600112233',
        'email' => 'contact@atlanticexport.com',
        'city' => 'Agadir',
        'country' => 'Morocco',
        'enable_plafond' => true,
        'plafond_amount' => 50000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
        'plafond_blocking_scope' => 'past_unpaid',
    ];

    $response = $this->postJson('/api/clients', $payload);
    $response->assertStatus(201);
    $client = $response->json('client');
    expect($client['name'])->toBe('Atlantic Export Co');
    expect((bool)$client['enable_plafond'])->toBeTrue();
    expect((float)$client['plafond_amount'])->toBe(50000.0);
    expect((bool)$client['block_on_plafond'])->toBeTrue();

    // List
    $listRes = $this->getJson('/api/clients');
    $listRes->assertStatus(200);
    expect(count($listRes->json('clients')))->toBeGreaterThanOrEqual(1);

    // Update
    $updateRes = $this->putJson("/api/clients/{$client['id']}", [
        'name' => 'Atlantic Export Co Ltd',
        'enable_plafond' => true,
        'plafond_amount' => 80000.00,
        'block_on_plafond' => false,
        'plafond_mode' => 'yearly',
    ]);
    $updateRes->assertStatus(200);
    expect($updateRes->json('client.name'))->toBe('Atlantic Export Co Ltd');
    expect((float)$updateRes->json('client.plafond_amount'))->toBe(80000.0);
    expect((bool)$updateRes->json('client.block_on_plafond'))->toBeFalse();

    // Delete
    $delRes = $this->deleteJson("/api/clients/{$client['id']}");
    $delRes->assertStatus(200);
    expect(Client::find($client['id']))->toBeNull();
});

test('can create, list, update, and delete a supplier with plafond settings', function () {
    $payload = [
        'name' => 'Maroc Fishery Supplier',
        'ice' => '009876543210002',
        'phone' => '+212611223344',
        'city' => 'Dakhla',
        'enable_plafond' => true,
        'plafond_amount' => 40000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
    ];

    $response = $this->postJson('/api/suppliers', $payload);
    $response->assertStatus(201);
    $supplier = $response->json('supplier');
    expect($supplier['name'])->toBe('Maroc Fishery Supplier');
    expect((bool)$supplier['enable_plafond'])->toBeTrue();
    expect((float)$supplier['plafond_amount'])->toBe(40000.0);

    // List
    $listRes = $this->getJson('/api/suppliers');
    $listRes->assertStatus(200);
    expect(count($listRes->json('suppliers')))->toBeGreaterThanOrEqual(1);

    // Delete
    $delRes = $this->deleteJson("/api/suppliers/{$supplier['id']}");
    $delRes->assertStatus(200);
    expect(Supplier::find($supplier['id']))->toBeNull();
});

test('check client plafond monthly within limit vs reached and blocked', function () {
    $client = Client::create([
        'name' => 'Ocean Best Client',
        'enable_plafond' => true,
        'plafond_amount' => 30000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
        'plafond_blocking_scope' => 'past_unpaid',
    ]);

    $service = app(PlafondService::class);

    // 1. Initially with 0 usage: within limit
    $check1 = $service->checkClientPlafond($client, '2026-03-15', 10000.0);
    expect($check1['is_reached'])->toBeFalse();
    expect($check1['is_blocked'])->toBeFalse();

    // 2. Add an invoice/shipping of 25,000 in March
    $sc = ShippingCategory::create(['name' => 'Vente Locale', 'enable_clients' => true]);
    Shipping::create([
        'date' => '2026-03-10',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'total_price' => 25000.0,
        'total_weight' => 500.0,
    ]);

    // Check with 4,000 new shipment (total = 29,000 < 30,000): still within limit
    $check2 = $service->checkClientPlafond($client, '2026-03-15', 4000.0);
    expect($check2['is_reached'])->toBeFalse();
    expect($check2['is_blocked'])->toBeFalse();

    // Check with 6,000 new shipment (total = 31,000 >= 30,000): reached and blocked!
    $check3 = $service->checkClientPlafond($client, '2026-03-15', 6000.0);
    expect($check3['is_reached'])->toBeTrue();
    expect($check3['is_blocked'])->toBeTrue();
    expect($check3['reason'])->toBe('current_month_reached');

    // 3. Client pays 10,000: credit released!
    ClientPayment::create([
        'client_id' => $client->id,
        'date' => '2026-03-12',
        'payment_type' => 'cash',
        'amount' => 10000.0,
    ]);

    // Usage is now 25,000 - 10,000 = 15,000. With 6,000 new = 21,000 < 30,000: allowed!
    $check4 = $service->checkClientPlafond($client, '2026-03-15', 6000.0);
    expect($check4['is_reached'])->toBeFalse();
    expect($check4['is_blocked'])->toBeFalse();
});

test('check client plafond monthly blocks month 2 if month 1 reached plafond unpaid', function () {
    $client = Client::create([
        'name' => 'Multi Month Client',
        'enable_plafond' => true,
        'plafond_amount' => 30000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
        'plafond_blocking_scope' => 'past_unpaid',
    ]);

    $sc = ShippingCategory::create(['name' => 'Vente Export', 'enable_clients' => true]);

    // Month 1 (January): Client purchases 30,000 DH unpaid
    Shipping::create([
        'date' => '2026-01-15',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'total_price' => 30000.0,
        'total_weight' => 600.0,
    ]);

    $service = app(PlafondService::class);

    // In Month 2 (February): User wants to create bon of 5,000 DH
    // Since Month 1 has unpaid balance >= 30,000, Month 2 must be blocked!
    $checkFeb = $service->checkClientPlafond($client, '2026-02-10', 5000.0);
    expect($checkFeb['is_reached'])->toBeTrue();
    expect($checkFeb['is_blocked'])->toBeTrue();
    expect($checkFeb['reason'])->toBe('past_unpaid_reached');

    // Client pays Month 1 invoice in February
    ClientPayment::create([
        'client_id' => $client->id,
        'date' => '2026-01-20',
        'payment_type' => 'bank_transfer',
        'amount' => 20000.0, // Remaining unpaid past is now 10,000 < 30,000
    ]);

    // Now Month 2 is checked again with 5,000 DH:
    // Past unpaid (10,000) < 30,000, and current month projected (5,000) < 30,000 -> allowed!
    $checkFebAfter = $service->checkClientPlafond($client, '2026-02-10', 5000.0);
    expect($checkFebAfter['is_reached'])->toBeFalse();
    expect($checkFebAfter['is_blocked'])->toBeFalse();
});

test('check client plafond yearly cumulative limit across year', function () {
    $client = Client::create([
        'name' => 'Yearly Client',
        'enable_plafond' => true,
        'plafond_amount' => 100000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'yearly',
    ]);

    $sc = ShippingCategory::create(['name' => 'Vente Cumulee', 'enable_clients' => true]);

    // Month 1: 10,000
    Shipping::create([
        'date' => '2026-01-10',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'total_price' => 10000.0,
        'total_weight' => 200.0,
    ]);

    // Month 3: 40,000
    Shipping::create([
        'date' => '2026-03-10',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'total_price' => 40000.0,
        'total_weight' => 800.0,
    ]);

    // Month 5: 50,000 (Cumulative so far = 100,000)
    Shipping::create([
        'date' => '2026-05-10',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'total_price' => 50000.0,
        'total_weight' => 1000.0,
    ]);

    $service = app(PlafondService::class);

    // In Month 6: User wants to create bon of 5,000 DH
    // Cumulative (100,000 + 5,000 = 105,000 >= 100,000) -> blocked!
    $checkMonth6 = $service->checkClientPlafond($client, '2026-06-01', 5000.0);
    expect($checkMonth6['is_reached'])->toBeTrue();
    expect($checkMonth6['is_blocked'])->toBeTrue();
    expect($checkMonth6['projected_usage'])->toBe(105000.0);
});

test('shipping store is blocked with 422 when client reaches plafond', function () {
    $client = Client::create([
        'name' => 'Blocked Client',
        'enable_plafond' => true,
        'plafond_amount' => 20000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
    ]);

    $sc = ShippingCategory::create(['name' => 'Standard Sale', 'enable_clients' => true]);
    $dsc = DetailShippingCategory::create([
        'name' => 'Sardine 20Kg',
        'shipping_category_id' => $sc->id,
    ]);

    // Try to create a shipping of 25,000 DH (exceeds 20,000 plafond)
    $payload = [
        'date' => '2026-07-15',
        'shipping_category_id' => $sc->id,
        'client_id' => $client->id,
        'details' => [
            [
                'detail_shipping_category_id' => $dsc->id,
                'weight' => 1000,
                'unit_price' => 25,
                'total_price' => 25000,
            ]
        ]
    ];

    $response = $this->postJson('/api/shipping', $payload);
    $response->assertStatus(422);
    expect(strtolower($response->json('message')))->toContain('plafond');
});

test('charge store is blocked with 422 when supplier reaches plafond', function () {
    $supplier = Supplier::create([
        'name' => 'Blocked Supplier',
        'enable_plafond' => true,
        'plafond_amount' => 15000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
    ]);

    $cc = ChargeCategory::create(['name' => 'Achat Poisson', 'enable_suppliers' => true]);
    $dcc = DetailChargeCategory::create([
        'name' => 'Chinchard Brut',
        'charge_category_id' => $cc->id,
    ]);

    $payload = [
        'date' => '2026-08-10',
        'charge_category_id' => $cc->id,
        'supplier_id' => $supplier->id,
        'details' => [
            [
                'detail_charge_category_id' => $dcc->id,
                'weight' => 2000,
                'unit_price' => 10,
                'total_price' => 20000, // 20,000 > 15,000 plafond
            ]
        ]
    ];

    $response = $this->postJson('/api/charge', $payload);
    $response->assertStatus(422);
    expect(strtolower($response->json('message')))->toContain('plafond');
});

test('production store is blocked with 422 when supplier reaches plafond', function () {
    $supplier = Supplier::create([
        'name' => 'Blocked Prod Supplier',
        'enable_plafond' => true,
        'plafond_amount' => 10000.00,
        'block_on_plafond' => true,
        'plafond_mode' => 'monthly',
    ]);

    $pc = ProductionCategory::create(['name' => 'Transformation', 'enable_suppliers' => true]);
    $dpc = DetailProductionCategory::create([
        'name' => 'Filet Frais',
        'production_category_id' => $pc->id,
    ]);

    $payload = [
        'date' => '2026-09-05',
        'production_category_id' => $pc->id,
        'supplier_id' => $supplier->id,
        'details' => [
            [
                'detail_production_category_id' => $dpc->id,
                'weight' => 1000,
                'unit_price' => 15,
                'total_price' => 15000, // 15,000 > 10,000 plafond
            ]
        ]
    ];

    $response = $this->postJson('/api/productions', $payload);
    $response->assertStatus(422);
    expect(strtolower($response->json('message')))->toContain('plafond');
});
