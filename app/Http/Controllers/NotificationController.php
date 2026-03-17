<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notifikasi',
                    'body' => $notification->data['body'] ?? ($notification->data['message'] ?? ''),
                    'url' => $notification->data['url'] ?? null,
                    'is_unread' => $notification->read_at === null,
                    'read_at' => $notification->read_at,
                    'created_at' => optional($notification->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications,
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
