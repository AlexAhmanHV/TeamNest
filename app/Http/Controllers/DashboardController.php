<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Services\CurrentWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->forUser();

        if (! $workspace) {
            return view('dashboard', ['workspace' => null]);
        }

        $taskBase = DB::table('tasks')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->where('projects.workspace_id', $workspace->id)
            ->whereNull('tasks.deleted_at');

        $total = (clone $taskBase)->count();
        $done = (clone $taskBase)->where('tasks.status', TaskStatus::Done->value)->count();
        $overdue = (clone $taskBase)
            ->whereDate('tasks.due_date', '<', now()->toDateString())
            ->where('tasks.status', '!=', TaskStatus::Done->value)
            ->count();

        return view('dashboard', [
            'workspace' => $workspace,
            'total' => $total,
            'done' => $done,
            'overdue' => $overdue,
            'completionRate' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
            'recentActivities' => ActivityLog::query()
                ->with('actor')
                ->where('workspace_id', $workspace->id)
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
