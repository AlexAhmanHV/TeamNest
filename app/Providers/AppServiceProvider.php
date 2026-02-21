<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        Route::bind('project', static fn (string $value): Project => Project::withTrashed()->findOrFail($value));
        Route::bind('task', static fn (string $value): Task => Task::withTrashed()->findOrFail($value));
    }
}
