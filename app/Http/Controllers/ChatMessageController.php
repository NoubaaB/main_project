<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    /**
     * GET /api/chat-messages?before_id=xxx&per_page=20
     * Returns messages in descending order (newest first) with cursor pagination.
     * Frontend reverses them to display chronologically.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);

        $query = ChatMessage::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc');

        // Manual cursor pagination using ?before_id=
        $beforeId = $request->query('before_id');
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($perPage + 1)->get();

        $hasMore = $messages->count() > $perPage;
        if ($hasMore) {
            $messages = $messages->slice(0, $perPage);
        }

        $nextBeforeId = $hasMore ? $messages->last()->id : null;

        return response()->json([
            'messages' => $messages->map(fn($m) => [
                'id'        => $m->id,
                'role'      => $m->role,
                'text'      => $m->text,
                'ts'        => (int) ($m->created_at->timestamp * 1000),
                'reaction'  => $m->reaction,
                'file_url'  => $m->file_path ? asset('storage/' . $m->file_path) : null,
                'file_name' => $m->file_name,
            ])->values(),
            'has_more'       => $hasMore,
            'next_before_id' => $nextBeforeId,
        ]);
    }

    /**
     * POST /api/chat-messages
     * Save a single message.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:user,agent',
            'text' => 'required|string',
        ]);

        $msg = ChatMessage::create([
            'user_id' => $request->user()->id,
            'role'    => $data['role'],
            'text'    => $data['text'],
        ]);

        return response()->json([
            'message' => [
                'id'       => $msg->id,
                'role'     => $msg->role,
                'text'     => $msg->text,
                'ts'       => (int) ($msg->created_at->timestamp * 1000),
                'reaction' => $msg->reaction,
            ]
        ], 201);
    }

    /**
     * DELETE /api/chat-messages
     * Clear all messages for the authenticated user.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        ChatMessage::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Chat history cleared']);
    }

    /**
     * DELETE /api/chat-messages/{id}
     * Delete a single message by ID (only if owned by the authenticated user).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $deleted = ChatMessage::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Message deleted']);
        }

        return response()->json(['message' => 'Message not found'], 404);
    }

    /**
     * POST /api/chat-messages/{id}/react
     * Set or clear a reaction (like/dislike) on a message.
     */
    public function react(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'reaction' => 'nullable|in:like,dislike',
        ]);

        $msg = ChatMessage::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$msg) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        $msg->reaction = $data['reaction'];
        $msg->save();

        return response()->json([
            'message'  => 'Reaction updated',
            'reaction' => $msg->reaction,
        ]);
    }
}
