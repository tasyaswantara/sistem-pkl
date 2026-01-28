<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
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
                    'body' => $notification->data['body'] ?? '',
                    'read_at' => $notification->read_at,
                    'created_at' => optional($notification->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications,
        ]);
    }
}
