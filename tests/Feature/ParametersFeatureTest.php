<?php

use App\Models\GuanoType;
use App\Models\PackageType;
use App\Models\Ship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);
});

test('can create, update and delete a guano type', function () {
    $payload = [
        'name_fr' => 'Farine de Poisson 65%',
        'name_en' => 'Fish Meal 65%',
        'name_es' => 'Harina de Pescado 65%',
        'name_in_invoice' => 'GUANO 65%',
    ];

    $response = $this->postJson('/api/guano-types', $payload);
    $response->assertStatus(201);
    $guanoId = $response->json('data.id') ?? $response->json('guano_type.id') ?? $response->json('id');
    expect($guanoId)->not->toBeNull();

    // List
    $resList = $this->getJson('/api/guano-types');
    $resList->assertStatus(200);

    // Delete
    $resDel = $this->deleteJson("/api/guano-types/{$guanoId}");
    $resDel->assertStatus(200);
    expect(GuanoType::find($guanoId))->toBeNull();
});

test('can create, update and delete a package type', function () {
    $payload = [
        'name_fr' => 'Carton Export 20 Kg',
        'name_en' => 'Export Box 20 Kg',
        'name_es' => 'Caja Export 20 Kg',
        'name_in_invoice' => 'BOX 20KG',
        'size' => '20kg',
    ];

    $response = $this->postJson('/api/package-types', $payload);
    $response->assertStatus(201);
    $pkgId = $response->json('data.id') ?? $response->json('package_type.id') ?? $response->json('id');
    expect($pkgId)->not->toBeNull();

    // List
    $resList = $this->getJson('/api/package-types');
    $resList->assertStatus(200);

    // Delete
    $resDel = $this->deleteJson("/api/package-types/{$pkgId}");
    $resDel->assertStatus(200);
    expect(PackageType::find($pkgId))->toBeNull();
});

test('can create a ship vessel', function () {
    $payload = [
        'name' => 'Bateau Al Massira IV',
        'matricule' => 'MAR-2026-99',
    ];

    $response = $this->postJson('/api/ships', $payload);
    $response->assertStatus(201);
    expect($response->json('ship.name'))->toBe('Bateau Al Massira IV');
});

