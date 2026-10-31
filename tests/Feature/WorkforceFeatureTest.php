<?php

use App\Models\Workforce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);
});

test('can create a workforce employee', function () {
    $payload = [
        'full_name' => 'Ahmed Benali',
        'matricule' => 'WF-2026-001',
        'sex' => 'male',
        'daily_price' => 120.0,
        'nationality' => 'Marocain',
        'is_migrant' => false,
        'salary_statement' => true,
        'fixed_daily_pay' => true,
    ];

    $response = $this->postJson('/api/workforces', $payload);

    $response->assertStatus(201);
    expect($response->json('workforce.id'))->not->toBeNull();
    expect($response->json('workforce.matricule'))->toBe('WF-2026-001');
});

test('can list workforce employees', function () {
    Workforce::create([
        'full_name' => 'Fatima Zahra',
        'matricule' => 'WF-2026-002',
        'sex' => 'female',
    ]);

    $response = $this->getJson('/api/workforces');
    $response->assertStatus(200);
    expect($response->json('workforces'))->toBeArray();
});

test('can update a workforce employee', function () {
    $wf = Workforce::create([
        'full_name' => 'Omar Idrissi',
        'matricule' => 'WF-2026-003',
        'sex' => 'male',
        'daily_price' => 100,
    ]);

    $response = $this->putJson("/api/workforces/{$wf->id}", [
        'full_name' => 'Omar Idrissi Modifié',
        'matricule' => 'WF-2026-003',
        'sex' => 'male',
        'daily_price' => 150,
    ]);

    $response->assertStatus(200);
    expect($response->json('workforce.full_name'))->toBe('Omar Idrissi Modifié');
    expect((float)$response->json('workforce.daily_price'))->toBe(150.0);
});

test('can delete a workforce employee', function () {
    $wf = Workforce::create([
        'full_name' => 'Karim Tazi',
        'matricule' => 'WF-2026-004',
        'sex' => 'male',
    ]);

    $response = $this->deleteJson("/api/workforces/{$wf->id}");
    $response->assertStatus(200);

    expect(Workforce::find($wf->id))->toBeNull();
});

