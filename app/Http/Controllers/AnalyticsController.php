<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Services\CurrentWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();

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

        $byStatus = (clone $taskBase)
            ->select('tasks.status', DB::raw('count(*) as aggregate'))
            ->groupBy('tasks.status')
            ->pluck('aggregate', 'tasks.status');

        $workload = DB::table('tasks')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('users', 'users.id', '=', 'tasks.assigned_to_user_id')
            ->where('projects.workspace_id', $workspace->id)
            ->whereNull('tasks.deleted_at')
            ->where('tasks.status', '!=', TaskStatus::Done->value)
            ->select('users.name', DB::raw('count(*) as open_tasks'))
            ->groupBy('users.name')
            ->orderByDesc('open_tasks')
            ->limit(10)
            ->get();

        return view('analytics.index', [
            'workspace' => $workspace,
            'total' => $total,
            'done' => $done,
            'overdue' => $overdue,
            'completionRate' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
            'byStatus' => $byStatus,
            'workload' => $workload,
        ]);
    }
}
