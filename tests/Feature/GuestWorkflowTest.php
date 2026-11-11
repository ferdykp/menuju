<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_create_filter_update_and_soft_delete_guests(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $this->actingAs($owner);

        $created = $this->postJson("/api/workspaces/{$workspace->id}/guests", [
            'name' => 'Nadia Salsabila', 'email' => 'NADIA@example.test', 'group_name' => 'Teman kampus', 'side' => 'bride',
        ])->assertCreated()->assertJsonPath('guest.rsvp_status', 'pending')->assertJsonPath('guest.email', 'nadia@example.test');
        $guestId = $created->json('guest.id');
        $this->postJson("/api/workspaces/{$workspace->id}/guests", [
            'name' => 'Nadia Lain', 'email' => 'nadia@example.test',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->patchJson("/api/guests/{$guestId}", ['rsvp_status' => 'attending', 'plus_ones' => 2])
            ->assertOk()->assertJsonPath('guest.rsvp_status', 'attending');
        $this->getJson("/api/workspaces/{$workspace->id}/guests?rsvp_status=attending&q=Nadia")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.summary.attending', 3);
        $this->deleteJson("/api/guests/{$guestId}")->assertOk();
        $this->assertSoftDeleted('guests', ['id' => $guestId]);
    }

    public function test_guest_endpoints_are_scoped_to_active_workspace_members(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $other = User::create(['name' => 'Citra', 'email' => 'citra@example.test', 'password' => 'Wedding2027']);
        $workspace = $this->workspaceFor($owner);
        $otherWorkspace = $this->workspaceFor($other, 'Citra & Danu');
        $guest = $otherWorkspace->guests()->create(['name' => 'Tamu privat']);

        $this->actingAs($owner)->getJson("/api/workspaces/{$otherWorkspace->id}/guests")->assertForbidden();
        $this->getJson("/api/guests/{$guest->id}")->assertForbidden();
        $this->assertDatabaseCount('guests', 1);
    }

    private function workspaceFor(User $owner, string $name = 'Ayu & Bima'): Workspace
    {
        $workspace = $owner->ownedWorkspaces()->create(['name' => $name, 'partner_one_name' => $owner->name]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        return $workspace;
    }
}
