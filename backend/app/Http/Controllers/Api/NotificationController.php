<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /** The authenticated user's notifications (own only) + unread count. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $paginator = $user->notifications()->paginate(20);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (DatabaseNotification $n) => $this->shape($n))->values(),
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['data' => ['message' => 'Marked as read.']]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['data' => ['message' => 'All marked as read.']]);
    }

    /** @return array<string, mixed> */
    private function shape(DatabaseNotification $n): array
    {
        return [
            'id' => $n->id,
            'kind' => $n->data['kind'] ?? null,
            'message' => $n->data['message'] ?? '',
            'data' => $n->data,
            'read_at' => $n->read_at,
            'created_at' => $n->created_at,
        ];
    }
}
