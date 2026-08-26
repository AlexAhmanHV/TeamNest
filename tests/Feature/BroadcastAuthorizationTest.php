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

    protected function setUp(): void
    {
        // phpunit.xml forces BROADCAST_CONNECTION=null for the suite so that other
        // tests never trigger real broadcasts. The null driver's auth() is a no-op
        // that always returns 200 regardless of channel authorization, so these
        // tests (which specifically verify channel authorization logic) switch to
        // the pusher driver before the application boots, whose auth flow is pure
        // local HMAC signing against the real credentials already in .env - no
        // network call is made. This must happen before parent::setUp() boots the
        // app, since routes/channels.php registers its callbacks against whichever
        // broadcaster is active at boot time.
        putenv('BROADCAST_CONNECTION=pusher');
        $_ENV['BROADCAST_CONNECTION'] = 'pusher';
        $_SERVER['BROADCAST_CONNECTION'] = 'pusher';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('BROADCAST_CONNECTION=null');
        $_ENV['BROADCAST_CONNECTION'] = 'null';
        $_SERVER['BROADCAST_CONNECTION'] = 'null';

        parent::tearDown();
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
