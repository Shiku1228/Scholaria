<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;

class StudentNotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!Schema::hasTable('notifications')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                ],
            ]);
        }

        $items = $user->notifications()
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                return [
                    'id' => (string) $notification->id,
                    'type' => (string) $notification->type,
                    'title' => (string) data_get($notification->data, 'title', class_basename($notification->type)),
                    'message' => (string) data_get($notification->data, 'message', ''),
                    'url' => (string) data_get($notification->data, 'url', ''),
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $items,
                'unread_count' => (int) $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }
}
