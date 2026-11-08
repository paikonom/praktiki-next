<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
#gia thn index
use Illuminate\Contracts\View\View;
class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $user = $request->user('web');

        $notification = $user->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }
    public function index(Request $request): View
    {
        $user = $request->user('web');

        $filter = $request->query('filter', 'all');

        if ($filter === 'unread') {
            $query = $user->unreadNotifications();
        } elseif ($filter === 'read') {
            $query = $user->readNotifications();
        } else {
            $filter = 'all';
            $query = $user->notifications();
        }

        $notifications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }
}