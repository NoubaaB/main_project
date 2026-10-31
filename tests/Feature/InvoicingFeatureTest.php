<?php

use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\Shipping;
use App\Models\ShippingCategory;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->supplier = Supplier::create([
        'name' => 'Maroc Sea Supplier',
    ]);

    $this->client = Client::create([
        'name' => 'Pesca Europa Client',
    ]);

    $this->chargeCategory = ChargeCategory::create([
        'name' => 'Charge Cat Test',
    ]);

    $this->shippingCategory = ShippingCategory::create([
        'name' => 'Ship Cat Test',
    ]);

    $this->charge = Charge::create([
        'date' => now()->format('Y-m-d'),
        'charge_category_id' => $this->chargeCategory->id,
        'supplier_id' => $this->supplier->id,
        'total_weight' => 2000,
        'total_price' => 30000,
    ]);

    $this->shipping = Shipping::create([
        'date' => now()->format('Y-m-d'),
        'shipping_category_id' => $this->shippingCategory->id,
        'client_id' => $this->client->id,
        'total_weight' => 1500,
        'total_price' => 25000,
    ]);
});

test('can create a supplier invoice linked to charges', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'supplier_id' => $this->supplier->id,
        'tracking_numbers' => ['FAC-SUP-2026-001'],
        'charge_ids' => [$this->charge->id],
        'type_invoice' => 'local',
    ];

    $response = $this->postJson('/api/supplier_invoice', $payload);

    $response->assertStatus(200);
    expect($response->json('supplier_invoice.id'))->not->toBeNull();
    expect((float)$response->json('supplier_invoice.total_price'))->toBe(30000.0);
});

test('can list supplier invoices', function () {
    SupplierInvoice::create([
        'date' => now()->format('Y-m-d'),
        'supplier_id' => $this->supplier->id,
        'tracking_numbers' => ['FAC-SUP-002'],
        'total_price' => 10000,
        'total_weight' => 1000,
    ]);

    $response = $this->getJson('/api/supplier_invoice');
    $response->assertStatus(200);
    expect($response->json('supplier_invoices'))->not->toBeNull();
});

test('can create a client invoice linked to shippings', function () {
    $payload = [
        'date' => now()->format('Y-m-d'),
        'client_id' => $this->client->id,
        'tracking_numbers' => ['FAC-CLI-2026-001'],
        'shipping_ids' => [$this->shipping->id],
        'type_invoice' => 'local',
    ];

    $response = $this->postJson('/api/client_invoice', $payload);

    $response->assertStatus(200);
    expect($response->json('client_invoice.id'))->not->toBeNull();
    expect((float)$response->json('client_invoice.total_price'))->toBe(25000.0);
});

test('can list client invoices', function () {
    ClientInvoice::create([
        'date' => now()->format('Y-m-d'),
        'client_id' => $this->client->id,
        'tracking_numbers' => ['FAC-CLI-002'],
        'total_price' => 12000,
        'total_weight' => 800,
    ]);

    $response = $this->getJson('/api/client_invoice');
    $response->assertStatus(200);
    expect($response->json('client_invoices'))->not->toBeNull();
});
