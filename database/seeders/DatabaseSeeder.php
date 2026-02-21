<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $member = User::updateOrCreate(
            ['email' => 'member@example.com'],
            ['name' => 'Member User', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-workspace'],
            ['name' => 'Demo Workspace', 'owner_user_id' => $admin->id]
        );

        $workspace->users()->syncWithoutDetaching([
            $admin->id => ['role' => 'admin', 'joined_at' => now()],
            $member->id => ['role' => 'member', 'joined_at' => now()],
        ]);

        $projectA = Project::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Client Onboarding'],
            ['description' => 'Onboarding tasks', 'created_by_user_id' => $admin->id]
        );

        $projectB = Project::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Internal Platform'],
            ['description' => 'Internal improvements', 'created_by_user_id' => $admin->id]
        );

        Task::whereIn('project_id', [$projectA->id, $projectB->id])->delete();

        foreach (range(1, 10) as $i) {
            Task::create([
                'project_id' => $i <= 5 ? $projectA->id : $projectB->id,
                'title' => "Seed Task {$i}",
                'description' => "Description {$i}",
                'status' => [TaskStatus::Todo->value, TaskStatus::Doing->value, TaskStatus::Done->value][$i % 3],
                'priority' => [TaskPriority::Low->value, TaskPriority::Med->value, TaskPriority::High->value][$i % 3],
                'due_date' => now()->addDays($i - 6)->toDateString(),
                'assigned_to_user_id' => $i % 2 ? $member->id : null,
                'created_by_user_id' => $admin->id,
            ]);
        }

        ActivityLog::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $admin->id,
            'action' => 'workspace.created',
            'metadata' => ['seed' => true],
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $admin->id,
            'action' => 'project.created',
            'metadata' => ['project' => $projectA->name],
            'created_at' => now()->subMinutes(5),
        ]);

        ActivityLog::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $member->id,
            'action' => 'task.updated',
            'metadata' => ['before' => ['status' => 'todo'], 'after' => ['status' => 'doing']],
            'created_at' => now()->subMinutes(2),
        ]);
    }
}
