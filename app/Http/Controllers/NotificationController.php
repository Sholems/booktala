<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * In-app notifications for staff and head office: job assignments, escalations
 * and missed-shift alerts. Opening one marks it read and follows its link.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $portal = $request->routeIs('admin.*') ? 'admin' : 'staff';

        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(25),
            'layout' => 'layouts.'.$portal,
            'routePrefix' => $portal.'.notifications',
        ]);
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only follow links into this app.
        if ($url && str_starts_with($url, url('/'))) {
            return redirect()->to($url);
        }

        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read');
    }
}
