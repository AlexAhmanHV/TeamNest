<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // phpunit.xml forces BROADCAST_CONNECTION=null for the suite so that other
    // tests never trigger real broadcasts. The null driver's auth() is a no-op
    // that always returns 200 regardless of channel authorization, so these
    // tests (which specifically verify channel authorization logic) switch to
    // the pusher driver, whose auth flow is pure local HMAC signing against
    // fixed test credentials - no network call is made and no real Pusher
    // credentials are required.
    //
    // The config() override is applied via an app "booting" callback (fired
    // after config files are loaded but before service providers boot) rather
    // than a plain setUp() call, because routes/channels.php registers its
    // Broadcast::channel() callbacks against whichever broadcaster is the
    // default connection at boot time. Setting config() after parent::setUp()
    // (i.e. after boot already completed) would only affect a broadcaster
    // instance that never received those channel registrations.
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->booting(function () {
            config([
                'broadcasting.default' => 'pusher',
                'broadcasting.connections.pusher.key' => 'test-key',
                'broadcasting.connections.pusher.secret' => 'test-secret',
                'broadcasting.connections.pusher.app_id' => 'test-app-id',
            ]);
        });

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function test_user_in_projects_workspace_can_authorize_the_project_presence_channel(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-project.{$project->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_outside_projects_workspace_cannot_authorize_the_project_presence_channel(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $owner->id]);
        $workspace->users()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $owner->id]);

        $this->actingAs($outsider)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-project.{$project->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }

    public function test_user_who_can_view_the_task_can_authorize_the_task_channel(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-task.{$task->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_outside_the_tasks_workspace_cannot_authorize_the_task_channel(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $owner->id]);
        $workspace->users()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $owner->id]);

        $this->actingAs($outsider)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-task.{$task->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }

    public function test_user_can_authorize_their_own_notification_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$user->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_cannot_authorize_another_users_notification_channel(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$otherUser->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }
}
