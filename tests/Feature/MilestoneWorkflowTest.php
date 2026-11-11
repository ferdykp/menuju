<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_members_can_manage_milestones_and_view_related_tasks(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $this->actingAs($owner);

        $created = $this->postJson("/api/workspaces/{$workspace->id}/milestones", [
            'title' => 'Kunci venue', 'description' => 'Bandingkan jadwal dan kapasitas.', 'category' => 'Venue', 'target_date' => '2027-02-01',
        ])->assertCreated()->assertJsonPath('milestone.status', 'upcoming')
            ->assertJsonPath('milestone.target_date', '2027-02-01');
        $milestoneId = $created->json('milestone.id');

        $this->postJson("/api/workspaces/{$workspace->id}/tasks", [
            'milestone_id' => $milestoneId, 'title' => 'Kunjungi venue', 'category' => 'Venue', 'priority' => 'normal',
        ])->assertCreated()->assertJsonPath('task.milestone.title', 'Kunci venue');

        $this->getJson("/api/workspaces/{$workspace->id}/milestones")
            ->assertOk()->assertJsonPath('milestones.0.title', 'Kunci venue')
            ->assertJsonPath('milestones.0.tasks_count', 1)
            ->assertJsonPath('milestones.0.tasks.0.title', 'Kunjungi venue');

        $this->patchJson("/api/workspaces/{$workspace->id}/milestones/{$milestoneId}", ['status' => 'done'])
            ->assertOk()->assertJsonPath('milestone.status', 'done');
        $this->deleteJson("/api/workspaces/{$workspace->id}/milestones/{$milestoneId}")->assertNoContent();
        $this->assertSoftDeleted('milestones', ['id' => $milestoneId]);
    }

    public function test_members_cannot_read_or_attach_another_workspaces_milestone(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $other = User::create(['name' => 'Citra', 'email' => 'citra@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $otherWorkspace = $this->workspaceFor($other, 'Citra & Danu');
        $foreignMilestone = $otherWorkspace->milestones()->create(['title' => 'Privat']);

        $this->actingAs($owner)->getJson("/api/workspaces/{$otherWorkspace->id}/milestones")->assertForbidden();
        $this->postJson("/api/workspaces/{$workspace->id}/tasks", [
            'title' => 'Salah workspace', 'category' => 'Umum', 'priority' => 'normal', 'milestone_id' => $foreignMilestone->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('milestone_id');
    }

    private function workspaceFor(User $owner, string $name = 'Ayu & Bima'): Workspace
    {
        $workspace = $owner->ownedWorkspaces()->create(['name' => $name, 'partner_one_name' => $owner->name]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        return $workspace;
    }
}
