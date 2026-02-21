<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\CurrentWorkspace;
use Illuminate\Http\JsonResponse;

class TaskApiController extends Controller
{
    public function index(Project $project, CurrentWorkspace $currentWorkspace): JsonResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($project->workspace_id === $workspace->id, 404);

        $tasks = $project->tasks()->with('assignee')->latest()->get();

        return response()->json(['data' => $tasks]);
    }
}
