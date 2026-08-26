<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request, CurrentWorkspace $currentWorkspace): JsonResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        $q = trim((string) $request->input('q', ''));

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json(['projects' => [], 'tasks' => [], 'members' => []]);
        }

        return response()->json([
            'projects' => Project::where('workspace_id', $workspace->id)
                ->where('name', 'like', "%{$q}%")->limit(5)->get(['id', 'name']),
            'tasks' => Task::whereHas('project', fn ($p) => $p->where('workspace_id', $workspace->id))
                ->where('title', 'like', "%{$q}%")->limit(5)->get(['id', 'title']),
            'members' => $workspace->users()->where('name', 'like', "%{$q}%")->limit(5)
                ->get(['users.id', 'users.name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->values(),
        ]);
    }
}
