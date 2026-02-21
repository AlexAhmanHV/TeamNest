<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrentWorkspace;
use Illuminate\Http\JsonResponse;

class ProjectApiController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): JsonResponse
    {
        $workspace = $currentWorkspace->requireForUser();

        $projects = $workspace->projects()->withCount('tasks')->orderBy('name')->get();

        return response()->json(['data' => $projects]);
    }
}
