<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebPushController extends Controller
{
    protected WebPushService $webPushService;

    public function __construct(WebPushService $webPushService)
    {
        $this->webPushService = $webPushService;
    }

    /**
     * Return VAPID Public Key so frontend can initialize PushManager.
     */
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        return response()->json([
            'status'     => true,
            'publicKey'  => $publicKey,
        ]);
    }

    /**
     * Store or update a Web Push subscription for the authenticated user.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint'         => 'required|string',
            'keys.p256dh'      => 'required|string',
            'keys.auth'        => 'required|string',
            'content_encoding' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $endpoint = $validated['endpoint'];
        $publicKey = $validated['keys']['p256dh'];
        $authToken = $validated['keys']['auth'];
        $contentEncoding = $validated['content_encoding'] ?? 'aes128gcm';
        $userAgent = $request->userAgent();

        // Update existing device subscription or create new
        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id'          => $userId,
                'public_key'       => $publicKey,
                'auth_token'       => $authToken,
                'content_encoding' => $contentEncoding,
                'user_agent'       => $userAgent,
            ]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Web push subscription registered successfully.',
            'data'    => [
                'id'         => $subscription->id,
                'created_at' => $subscription->created_at,
            ]
        ]);
    }

    /**
     * Remove a Web Push subscription for the given endpoint.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $userId = Auth::id();
        $endpoint = $request->input('endpoint');

        PushSubscription::where('endpoint', $endpoint)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Web push subscription removed successfully.'
        ]);
    }

    /**
     * Get current user's push notification subscription status.
     */
    public function status(): JsonResponse
    {
        $userId = Auth::id();
        $count = PushSubscription::where('user_id', $userId)->count();

        return response()->json([
            'status'        => true,
            'is_subscribed' => $count > 0,
            'device_count'  => $count,
        ]);
    }

    /**
     * Send an instant test Web Push notification to current user's subscribed devices.
     */
    public function testNotification(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = [
            'title'   => 'Jupiter ERP • Test Push Notification',
            'body'    => "Hello {$user->name}! Web Push notifications are active and ready.",
            'icon'    => '/images/jupiter-logo.png',
            'badge'   => '/favicon.ico',
            'tag'     => 'jupiter-test-' . time(),
            'renotify'=> true,
            'data'    => [
                'model'     => 'System',
                'action'    => 'test',
                'record_id' => 0,
                'url'       => '/',
                'timestamp' => time() * 1000,
            ],
        ];

        $sentCount = $this->webPushService->sendToUser($user->id, $payload);

        return response()->json([
            'status'     => true,
            'message'    => "Test notification dispatched to {$sentCount} device(s).",
            'sent_count' => $sentCount,
        ]);
    }
}
