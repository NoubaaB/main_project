<?php

namespace App\Http\Controllers;

use App\Models\AiQueryMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiQueryMessageController extends Controller
{
    /**
     * GET /api/ai-query-messages?before_id=xxx&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);

        $query = AiQueryMessage::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc');

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
                'id'       => $m->id,
                'role'     => $m->role,
                'text'     => $m->text,
                'ts'       => (int) ($m->created_at->timestamp * 1000),
                'reaction' => $m->reaction,
            ])->values(),
            'has_more'       => $hasMore,
            'next_before_id' => $nextBeforeId,
        ]);
    }

    /**
     * POST /api/ai-query-messages
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:user,agent',
            'text' => 'required|string',
        ]);

        $msg = AiQueryMessage::create([
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
     * DELETE /api/ai-query-messages
     */
    public function destroyAll(Request $request): JsonResponse
    {
        AiQueryMessage::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'AI query chat history cleared']);
    }

    /**
     * DELETE /api/ai-query-messages/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $deleted = AiQueryMessage::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Message deleted']);
        }

        return response()->json(['message' => 'Message not found'], 404);
    }

    /**
     * POST /api/ai-query-messages/{id}/react
     */
    public function react(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'reaction' => 'nullable|in:like,dislike',
        ]);

        $msg = AiQueryMessage::where('id', $id)
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
