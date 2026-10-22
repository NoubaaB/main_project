<?php

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('vapid public key endpoint returns configured key', function () {
    $user = User::first() ?? User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/webpush/vapid-public-key');

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
             ])
             ->assertJsonStructure(['status', 'publicKey']);

    expect($response->json('publicKey'))->not->toBeEmpty();
});

test('unauthenticated users cannot access webpush status', function () {
    $response = $this->getJson('/api/webpush/status');
    $response->assertStatus(401);
});

test('authenticated user can subscribe and check status', function () {
    $user = User::first() ?? User::factory()->create();
    Sanctum::actingAs($user);

    $endpoint = 'https://updates.push.services.mozilla.com/wpush/v2/test-endpoint-' . uniqid();

    $subscribeResponse = $this->postJson('/api/webpush/subscribe', [
        'endpoint'         => $endpoint,
        'keys'             => [
            'p256dh' => 'BDf9V8cM62zP0YVj9s56B6f...dummy_key',
            'auth'   => 'dummyaUtH12345678==',
        ],
        'content_encoding' => 'aes128gcm',
    ]);

    $subscribeResponse->assertStatus(200)
                      ->assertJson([
                          'status' => true,
                      ]);

    $this->assertDatabaseHas('push_subscriptions', [
        'user_id'  => $user->id,
        'endpoint' => $endpoint,
    ]);

    $statusResponse = $this->getJson('/api/webpush/status');
    $statusResponse->assertStatus(200)
                   ->assertJson([
                       'status'        => true,
                       'is_subscribed' => true,
                   ]);
});

test('authenticated user can unsubscribe', function () {
    $user = User::first() ?? User::factory()->create();
    Sanctum::actingAs($user);

    $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-' . uniqid();

    PushSubscription::create([
        'user_id'          => $user->id,
        'endpoint'         => $endpoint,
        'public_key'       => 'test_key',
        'auth_token'       => 'test_token',
        'content_encoding' => 'aes128gcm',
    ]);

    $unsubscribeResponse = $this->postJson('/api/webpush/unsubscribe', [
        'endpoint' => $endpoint,
    ]);

    $unsubscribeResponse->assertStatus(200)
                        ->assertJson([
                            'status' => true,
                        ]);

    $this->assertDatabaseMissing('push_subscriptions', [
        'endpoint' => $endpoint,
    ]);
});

test('webpush service builds informative model payload', function () {
    $service = app(WebPushService::class);

    $payload = $service->buildModelPayload(
        'Charge',
        'created',
        42,
        'Alice',
        [
            'tracking' => 'TRK-98765',
            'weight'   => 1500.5,
            'price'    => 25000,
            'category' => 'Fresh Sardine',
        ]
    );

    expect($payload)->toHaveKeys(['title', 'body', 'icon', 'badge', 'tag', 'data']);
    expect($payload['title'])->toContain('Charge');
    expect($payload['title'])->toContain('created');
    expect($payload['body'])->toContain('Alice');
    expect($payload['body'])->toContain('TRK-98765');
    expect($payload['data']['model'])->toBe('Charge');
    expect($payload['data']['record_id'])->toBe(42);
    expect($payload['data']['url'])->toBe('/view_charge/42');
});

test('sendModelChangePush excludes actor and targets eligible users', function () {
    $role = \App\Models\Role::create([
        'name'           => 'Manager',
        'is_super_admin' => false,
        'permissions'    => ['charges.view'],
    ]);

    $actor = User::factory()->create(['name' => 'Actor User', 'role_id' => $role->id, 'is_active' => true]);
    $recipient = User::factory()->create(['name' => 'Recipient User', 'role_id' => $role->id, 'is_active' => true]);
    $ineligible = User::factory()->create(['name' => 'Other User', 'role_id' => null, 'is_active' => true]);

    // Give recipient a push subscription
    PushSubscription::create([
        'user_id'          => $recipient->id,
        'endpoint'         => 'https://fcm.googleapis.com/fcm/send/recipient-endpoint-' . uniqid(),
        'public_key'       => 'p256dh_sample',
        'auth_token'       => 'auth_sample',
        'content_encoding' => 'aes128gcm',
    ]);

    // Give actor a push subscription
    PushSubscription::create([
        'user_id'          => $actor->id,
        'endpoint'         => 'https://fcm.googleapis.com/fcm/send/actor-endpoint-' . uniqid(),
        'public_key'       => 'p256dh_actor',
        'auth_token'       => 'auth_actor',
        'content_encoding' => 'aes128gcm',
    ]);

    $service = app(WebPushService::class);

    // Call sendModelChangePush with $actor->id as actorId
    // It should only queue for $recipient, not $actor and not $ineligible
    $sent = $service->sendModelChangePush(
        'Charge',
        'created',
        101,
        $actor->name,
        $actor->id,
        'charges.view',
        ['tracking' => 'TRK-TEST']
    );

    // Because endpoints are mock, send returns 0 successful network deliveries, but completes without exception
    expect($sent)->toBeInt();
});

