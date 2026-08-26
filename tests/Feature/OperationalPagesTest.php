<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OperationalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_user_id' => $this->user->id]);
        $this->workspace->users()->attach($this->user->id, ['role' => 'admin', 'joined_at' => now()]);
    }

    public function getSession()
    {
        return ['current_workspace_id' => $this->workspace->id];
    }

    public function test_activity_page_uses_design_system()
    {
        $response = $this->actingAs($this->user)->withSession($this->getSession())->get(route('activity.index'));
        $response->assertStatus(200);
        $response->assertSeeText('Activity Feed');
        $response->assertSee('tn-card');
        $response->assertSee('tn-page-title');
        $response->assertSee('divide-slate-800');
    }

    public function test_notifications_page_uses_design_system()
    {
        $response = $this->actingAs($this->user)->withSession($this->getSession())->get(route('notifications.index'));
        $response->assertStatus(200);
        $response->assertSeeText('Notifications');
        $response->assertSee('tn-card');
        $response->assertSee('tn-page-title');
        $response->assertSee('divide-slate-800');
    }

    public function test_analytics_page_uses_design_system()
    {
        $response = $this->actingAs($this->user)->withSession($this->getSession())->get(route('analytics.index'));
        $response->assertStatus(200);
        $response->assertSeeText('Analytics');
        $response->assertSee('tn-card');
        $response->assertSee('tn-page-title');
    }

    public function test_tokens_page_uses_design_system()
    {
        $response = $this->actingAs($this->user)->withSession($this->getSession())->get(route('tokens.index'));
        $response->assertStatus(200);
        $response->assertSeeText('API Tokens');
        $response->assertSee('tn-card');
        $response->assertSee('tn-page-title');
        $response->assertSee('tn-table');
        $response->assertSee('tn-input');
    }

    public function test_tokens_create_action_responds()
    {
        // Create token - just verify it responds with a redirect
        $createResponse = $this->actingAs($this->user)->withSession($this->getSession())->post(route('tokens.store'), [
            'name' => 'Test Token for Verification',
        ]);
        $createResponse->assertStatus(302);
    }
}
