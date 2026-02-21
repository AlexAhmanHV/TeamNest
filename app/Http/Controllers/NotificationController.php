<?php

namespace App\Http\Controllers;

use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();

        $notifications = auth()->user()
            ->workspaceNotifications()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'workspace' => $workspace,
        ]);
    }

    public function markAllRead(CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();

        auth()->user()
            ->workspaceNotifications()
            ->where('workspace_id', $workspace->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }
}
