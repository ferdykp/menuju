<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_assign_complete_and_delete_a_workspace_task(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $partner = User::create(['name' => 'Bima', 'email' => 'bima@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $workspace->members()->attach($partner->id, ['role' => 'partner', 'status' => 'active']);
        $this->actingAs($partner);

        $created = $this->postJson("/api/workspaces/{$workspace->id}/tasks", [
            'title' => 'Pilih fotografer',
            'description' => 'Bandingkan tiga penawaran.',
            'category' => 'Vendor',
            'assignee_id' => $partner->id,
            'due_at' => '2027-04-15',
            'priority' => 'high',
        ]);

        $created->assertCreated()
            ->assertJsonPath('task.title', 'Pilih fotografer')
            ->assertJsonPath('task.assignee.name', 'Bima')
            ->assertJsonPath('task.status', 'planned');

        $taskId = $created->json('task.id');
        $this->patchJson("/api/tasks/{$taskId}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('task.status', 'completed');

        $this->getJson("/api/workspaces/{$workspace->id}/tasks")
            ->assertOk()
            ->assertJsonPath('meta.summary.completed', 1)
            ->assertJsonPath('meta.summary.percent', 100);

        $this->deleteJson("/api/tasks/{$taskId}")->assertOk();
        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
    }

    public function test_task_ids_cannot_be_used_to_read_another_workspace(): void
    {
        $ownerOne = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $ownerTwo = User::create(['name' => 'Citra', 'email' => 'citra@example.test', 'password' => 'Wedding2027']);
        $workspaceOne = $this->workspaceFor($ownerOne, 'Ayu & Bima');
        $workspaceTwo = $this->workspaceFor($ownerTwo, 'Citra & Danu');
        $task = $workspaceTwo->tasks()->create([
            'created_by' => $ownerTwo->id,
            'title' => 'Data privat',
            'category' => 'Lainnya',
            'priority' => 'normal',
            'status' => 'planned',
        ]);

        $this->actingAs($ownerOne)
            ->getJson("/api/tasks/{$task->id}")
            ->assertForbidden();

        $this->getJson("/api/workspaces/{$workspaceOne->id}/tasks")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_home_summary_uses_real_task_records_and_empty_state_counts(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $this->actingAs($owner);

        $this->getJson("/api/workspaces/{$workspace->id}/home")
            ->assertOk()
            ->assertJsonPath('tasks.total', 0)
            ->assertJsonPath('tasks.completion_percent', 0);

        $workspace->tasks()->create([
            'created_by' => $owner->id,
            'title' => 'Tentukan tanggal fitting',
            'category' => 'Busana',
            'due_at' => today()->addDays(3)->toDateString(),
            'priority' => 'normal',
            'status' => 'planned',
        ]);

        $this->getJson("/api/workspaces/{$workspace->id}/home")
            ->assertOk()
            ->assertJsonPath('tasks.total', 1)
            ->assertJsonPath('tasks.due_this_week.0.title', 'Tentukan tanggal fitting');
    }

    private function workspaceFor(User $owner, string $name = 'Ayu & Bima'): Workspace
    {
        $workspace = $owner->ownedWorkspaces()->create([
            'name' => $name,
            'partner_one_name' => $owner->name,
            'partner_two_name' => 'Bima',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        return $workspace;
    }
}
