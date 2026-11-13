<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Mail\RegistrationVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_session_and_hashes_the_password(): void
    {
        Mail::fake();
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ayu Pratami',
            'email' => 'ayu@example.test',
            'password' => 'Wedding2027',
            'password_confirmation' => 'Wedding2027',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('email', 'ayu@example.test');

        $code = null;
        Mail::assertSent(RegistrationVerificationCode::class, function (RegistrationVerificationCode $mail) use (&$code): bool {
            $code = $mail->code;
            return true;
        });
        $this->assertIsString($code);
        $this->assertGuest();
        $this->assertDatabaseHas('pending_email_verifications', ['email' => 'ayu@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'ayu@example.test']);

        $this->postJson('/api/auth/register/verify', [
            'email' => 'ayu@example.test',
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('user.name', 'Ayu Pratami')
            ->assertJsonPath('user.workspaces', []);

        $user = User::where('email', 'ayu@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Wedding2027', $user->password));
    }

    public function test_workspace_creation_adds_the_owner_to_its_active_membership(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $this->actingAs($owner);

        $response = $this->postJson('/api/workspaces', [
            'name' => 'Ayu & Bima',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-08-21',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response->assertCreated()->assertJsonPath('workspace.name', 'Ayu & Bima');
        $workspace = Workspace::firstOrFail();
        $this->assertTrue($workspace->members()->where('users.id', $owner->id)->wherePivot('status', 'active')->exists());
        $this->assertSame('owner', $workspace->members()->where('users.id', $owner->id)->first()->pivot->role);
    }

    public function test_partner_cannot_invite_another_member_without_owner_authority(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $partner = User::create(['name' => 'Bima', 'email' => 'bima@example.test', 'password' => 'Wedding2027']);
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Ayu & Bima',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->members()->attach($partner->id, ['role' => 'partner', 'status' => 'active']);

        $this->actingAs($partner)
            ->postJson("/api/workspaces/{$workspace->id}/invites", ['email' => 'guest@example.test'])
            ->assertForbidden();
    }

    public function test_invitation_link_shows_limited_details_and_acceptance_adds_the_partner(): void
    {
        $owner = User::create(['name' => 'Ayu', 'email' => 'ayu@example.test', 'password' => 'Wedding2027']);
        $partner = User::create(['name' => 'Bima', 'email' => 'bima@example.test', 'password' => 'Wedding2027']);
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Ayu & Bima',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->invitations()->create([
            'invited_by' => $owner->id,
            'email' => $partner->email,
            'token_hash' => hash('sha256', 'secure-invite-token'),
            'expires_at' => now()->addDay(),
        ]);

        $this->getJson('/api/invitations/secure-invite-token')
            ->assertOk()
            ->assertJsonPath('workspace_name', 'Ayu & Bima')
            ->assertJsonPath('email', 'b•••@example.test')
            ->assertJsonMissingPath('token');

        $this->actingAs($partner)
            ->postJson('/api/invitations/secure-invite-token/accept')
            ->assertOk();

        $this->assertTrue($workspace->members()->where('users.id', $partner->id)->wherePivot('status', 'active')->exists());
        $this->assertNotNull(WorkspaceInvitation::firstOrFail()->accepted_at);

        $this->actingAs($owner)
            ->postJson("/api/workspaces/{$workspace->id}/invites", ['email' => 'another@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
