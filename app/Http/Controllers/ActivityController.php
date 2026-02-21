<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\CurrentWorkspace;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();

        return view('activity.index', [
            'workspace' => $workspace,
            'activities' => ActivityLog::query()
                ->with(['actor', 'subject'])
                ->where('workspace_id', $workspace->id)
                ->latest('created_at')
                ->paginate(30),
        ]);
    }
}
