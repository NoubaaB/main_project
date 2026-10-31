<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can authenticate via login endpoint', function () {
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(201);
    expect($response->json('token'))->not->toBeNull();
    expect($response->json('user.email'))->toBe('test@test.com');
});

test('unauthenticated user cannot access protected me endpoint', function () {
    $response = $this->getJson('/api/me');
    $response->assertStatus(401);
});

test('authenticated user can access protected me endpoint', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/me');
    $response->assertStatus(200);
    expect($response->json('user.id') ?? $response->json('id'))->toBe($user->id);
});
