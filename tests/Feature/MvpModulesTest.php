<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MvpModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_needs_and_purchase_workflows_persist_workspace_data(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $event = $this->postJson("/api/workspaces/{$workspace->id}/events", [
            'title' => 'Akad', 'event_type' => 'ceremony', 'event_date' => '2027-05-12', 'venue_name' => 'Taman',
        ])->assertCreated()->json('event');
        $this->postJson("/api/events/{$event['id']}/agenda-items", ['title' => 'Kedatangan keluarga', 'start_time' => '08:00'])
            ->assertCreated()->assertJsonPath('agenda_item.title', 'Kedatangan keluarga');
        $need = $this->postJson("/api/workspaces/{$workspace->id}/needs", [
            'title' => 'Dekorasi meja', 'estimated_amount' => 1500000, 'priority' => 'high', 'needed_by' => '2027-04-28',
            'status' => 'purchased',
        ])->assertCreated()->assertJsonPath('need.needed_by', '2027-04-28')->json('need');
        $this->assertSame('planned', $need['status']);
        $this->assertDatabaseHas('needs', ['id' => $need['id'], 'status' => 'planned', 'purchased_amount' => null]);
        $this->patchJson("/api/needs/{$need['id']}", ['status' => 'shortlisted'])->assertOk()->assertJsonPath('need.status', 'shortlisted');
        $this->patchJson("/api/needs/{$need['id']}", ['status' => 'purchased'])->assertUnprocessable();
        $this->getJson("/api/needs/{$need['id']}")->assertOk()->assertJsonPath('need.title', 'Dekorasi meja');
        $otherOwner = User::create(['name' => 'Rani', 'email' => uniqid('rani').'@example.test', 'password' => 'Wedding2027']);
        $this->actingAs($otherOwner)->getJson("/api/needs/{$need['id']}")->assertForbidden();
        $this->actingAs($user);
        $this->postJson("/api/needs/{$need['id']}/purchase", ['purchased_amount' => 1400000, 'purchased_at' => '2027-05-01'])
            ->assertOk()->assertJsonPath('need.status', 'purchased')->assertJsonPath('need.purchased_amount', '1400000.00')->assertJsonPath('need.purchased_at', '2027-05-01');
        $this->getJson("/api/workspaces/{$workspace->id}/events")->assertOk()->assertJsonCount(1, 'events');
    }

    public function test_needs_link_to_workspace_budget_categories_and_appear_in_budget_detail(): void
    {
        [$user, $workspace] = $this->workspace();
        [$otherUser, $otherWorkspace] = $this->workspace();
        $category = $workspace->budgetCategories()->create(['name' => 'Dekorasi', 'planned_amount' => 2500000]);
        $foreignCategory = $otherWorkspace->budgetCategories()->create(['name' => 'Privat', 'planned_amount' => 100000]);
        $this->actingAs($user);

        $this->postJson("/api/workspaces/{$workspace->id}/needs", [
            'title' => 'Bunga meja', 'category' => 'Dekorasi', 'estimated_amount' => 500000,
            'budget_category_id' => $foreignCategory->id,
        ])->assertUnprocessable();

        $need = $this->postJson("/api/workspaces/{$workspace->id}/needs", [
            'title' => 'Bunga meja', 'category' => 'Dekorasi', 'estimated_amount' => 500000,
            'budget_category_id' => $category->id,
        ])->assertCreated()->assertJsonPath('need.budget_category_id', $category->id)->assertJsonPath('need.budget_category', 'Dekorasi')->json('need');

        $this->getJson("/api/workspaces/{$workspace->id}/needs?budget_category_id={$category->id}")
            ->assertOk()->assertJsonCount(1, 'needs')->assertJsonPath('needs.0.budget_category.name', 'Dekorasi');
        $this->getJson("/api/budget-categories/{$category->id}")
            ->assertOk()->assertJsonPath('needs.0.id', $need['id'])->assertJsonPath('needs.0.title', 'Bunga meja');

        $this->deleteJson("/api/budget-categories/{$category->id}")->assertNoContent();
        $this->getJson("/api/needs/{$need['id']}")->assertOk()->assertJsonPath('need.budget_category_id', null);
    }

    public function test_event_detail_returns_its_agenda_and_is_scoped_to_the_workspace(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $event = $this->postJson("/api/workspaces/{$workspace->id}/events", [
            'title' => 'Resepsi', 'event_type' => 'reception', 'event_date' => '2027-08-21', 'venue_name' => 'Taman Kota',
        ])->assertCreated()->json('event');
        $this->postJson("/api/events/{$event['id']}/agenda-items", ['title' => 'Sambutan', 'start_time' => '10:00'])->assertCreated();

        $this->getJson("/api/workspaces/{$workspace->id}/events/{$event['id']}")
            ->assertOk()->assertJsonPath('event.title', 'Resepsi')->assertJsonPath('event.agenda_items.0.title', 'Sambutan');

        [$otherUser, $otherWorkspace] = $this->workspace();
        $this->actingAs($otherUser)->getJson("/api/workspaces/{$otherWorkspace->id}/events/{$event['id']}")->assertNotFound();
    }

    public function test_guest_csv_import_skips_duplicate_emails(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $this->postJson("/api/workspaces/{$workspace->id}/guests", ['name' => 'Dina', 'email' => 'dina@example.test'])->assertCreated();
        $this->postJson("/api/workspaces/{$workspace->id}/guests/import", ['guests' => [
            ['name' => 'Dina Duplikat', 'email' => 'DINA@example.test'],
            ['name' => 'Raka', 'email' => 'raka@example.test', 'group_name' => 'Keluarga'],
        ]])->assertCreated()->assertJsonPath('created', 1)->assertJsonCount(1, 'duplicates');
        $this->assertDatabaseCount('guests', 2);
    }

    public function test_account_ledger_transfer_and_savings_deposit_reconcile_without_double_counting(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $cash = $this->postJson("/api/workspaces/{$workspace->id}/accounts", ['name' => 'Kas', 'account_type' => 'cash', 'opening_balance' => 1000000])->assertCreated()->json('account');
        $bank = $this->postJson("/api/workspaces/{$workspace->id}/accounts", ['name' => 'Bank', 'account_type' => 'bank', 'opening_balance' => 0])->assertCreated()->json('account');
        $this->postJson("/api/workspaces/{$workspace->id}/transactions", ['type' => 'income', 'account_id' => $cash['id'], 'description' => 'Hadiah keluarga', 'amount' => 500000, 'occurred_at' => '2027-04-01'])
            ->assertCreated()->assertJsonPath('transaction.occurred_at', '2027-04-01');
        $this->postJson("/api/workspaces/{$workspace->id}/transactions", ['type' => 'transfer', 'account_id' => $cash['id'], 'destination_account_id' => $bank['id'], 'description' => 'Pindah dana', 'amount' => 250000, 'occurred_at' => '2027-04-02'])->assertCreated();
        $this->postJson("/api/workspaces/{$workspace->id}/transactions", ['type' => 'transfer', 'account_id' => $cash['id'], 'description' => 'Transfer tanpa tujuan', 'amount' => 1000, 'occurred_at' => '2027-04-02'])->assertUnprocessable();
        $goal = $this->postJson("/api/workspaces/{$workspace->id}/savings", ['name' => 'Bulan madu', 'target_amount' => 2000000, 'target_date' => '2027-12-20'])
            ->assertCreated()->assertJsonPath('goal.target_date', '2027-12-20')->json('goal');
        $deposit = $this->postJson("/api/savings-goals/{$goal['id']}/deposits", ['amount' => 100000, 'deposited_at' => '2027-04-03', 'account_id' => $bank['id']])
            ->assertCreated()->assertJsonPath('deposit.deposited_at', '2027-04-03')->json('deposit');
        $this->patchJson("/api/savings-goals/{$goal['id']}/deposits/{$deposit['id']}", ['amount' => 120000, 'deposited_at' => '2027-04-04', 'account_id' => $bank['id']])
            ->assertOk()->assertJsonPath('deposit.amount', '120000.00');
        $this->getJson("/api/workspaces/{$workspace->id}/accounts")->assertOk()->assertJsonPath('accounts.0.balance', 130000);
        $this->deleteJson("/api/savings-goals/{$goal['id']}/deposits/{$deposit['id']}")->assertNoContent();
        $this->getJson("/api/workspaces/{$workspace->id}/finance")->assertOk()->assertJsonPath('summary.balance', 1500000);
    }

    public function test_budget_obligation_payments_create_expenses_and_enforce_workspace_relations(): void
    {
        [$user, $workspace] = $this->workspace();
        $otherOwner = User::create(['name' => 'Rani', 'email' => uniqid('rani').'@example.test', 'password' => 'Wedding2027']);
        $otherWorkspace = $this->workspaceFor($otherOwner, 'Rani & Danu');
        $this->actingAs($user);
        $account = $this->postJson("/api/workspaces/{$workspace->id}/accounts", ['name' => 'Kas', 'account_type' => 'cash', 'opening_balance' => 1000000])->assertCreated()->json('account');
        $foreignCategory = $otherWorkspace->budgetCategories()->create(['name' => 'Privat', 'planned_amount' => 100000]);
        $this->postJson("/api/workspaces/{$workspace->id}/payment-obligations", ['title' => 'DP venue', 'total_amount' => 500000, 'budget_category_id' => $foreignCategory->id])->assertUnprocessable();
        $category = $this->postJson("/api/workspaces/{$workspace->id}/budgets", ['name' => 'Venue', 'planned_amount' => 800000])->assertCreated()->json('category');
        $obligation = $this->postJson("/api/workspaces/{$workspace->id}/payment-obligations", ['title' => 'DP venue', 'total_amount' => 500000, 'budget_category_id' => $category['id'], 'due_at' => '2027-04-15'])
            ->assertCreated()->assertJsonPath('obligation.due_at', '2027-04-15')->json('obligation');
        $this->getJson("/api/workspaces/{$workspace->id}/payment-obligations")
            ->assertOk()->assertJsonPath('obligations.0.due_at', '2027-04-15');
        $this->postJson("/api/payment-obligations/{$obligation['id']}/payments", ['account_id' => $account['id'], 'amount' => 200000, 'paid_at' => '2027-04-10'])
            ->assertOk()->assertJsonPath('payment.amount', '200000.00')->assertJsonPath('payment.paid_at', '2027-04-10')->assertJsonPath('payment.transaction.occurred_at', '2027-04-10');
        $this->postJson("/api/payment-obligations/{$obligation['id']}/payments", ['account_id' => $account['id'], 'amount' => 300001, 'paid_at' => '2027-04-11'])->assertUnprocessable();
        $this->getJson("/api/workspaces/{$workspace->id}/budgets")->assertOk()->assertJsonPath('summary.actual', 200000);
        $this->getJson("/api/budget-categories/{$category['id']}")->assertOk()->assertJsonPath('category.actual_amount', 200000)->assertJsonCount(1, 'obligations')->assertJsonCount(1, 'transactions');
        $this->getJson("/api/budget-categories/{$foreignCategory->id}")->assertForbidden();
    }

    public function test_public_invitation_requires_publish_and_personal_token_controls_rsvp(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $guest = $this->postJson("/api/workspaces/{$workspace->id}/guests", ['name' => 'Nadia', 'plus_ones' => 1])->assertCreated()->json('guest');
        $invitation = $this->postJson("/api/workspaces/{$workspace->id}/invitation", ['title' => 'Ayu & Bima', 'theme_key' => 'botanical'])->assertCreated()->json('invitation');
        $this->getJson("/api/public/invitations/{$invitation['slug']}")->assertNotFound();
        $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/publish", ['published' => true])->assertOk();
        $link = $this->postJson("/api/invitations/{$invitation['id']}/guests/{$guest['id']}/link")->assertOk()->json('url');
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $this->getJson("/api/public/invitations/{$invitation['slug']}?guest={$query['guest']}")
            ->assertOk()->assertJsonPath('invitation.guest_name', 'Nadia')->assertJsonPath('invitation.guest_plus_ones', 1);
        $this->postJson("/api/public/invitations/{$invitation['slug']}/rsvp", ['guest_token' => $query['guest'], 'rsvp_status' => 'attending', 'plus_ones' => 2])->assertUnprocessable();
        $this->postJson("/api/public/invitations/{$invitation['slug']}/rsvp", ['guest_token' => $query['guest'], 'rsvp_status' => 'attending', 'plus_ones' => 1])->assertOk();
        $this->getJson("/api/workspaces/{$workspace->id}/guests")->assertJsonPath('meta.summary.attending', 2);
    }

    public function test_invitation_motion_preferences_are_saved_and_shared_with_public_renderer(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $invitation = $this->postJson("/api/workspaces/{$workspace->id}/invitation", [
            'title' => 'Undangan Ayu & Bima', 'theme_key' => 'botanical',
        ])->assertCreated()
            ->assertJsonPath('invitation.motion_preset', 'theme')
            ->assertJsonPath('invitation.decorative_effects', true)
            ->json('invitation');

        $this->patchJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}", [
            'motion_preset' => 'cinematic', 'decorative_effects' => false,
        ])->assertOk()
            ->assertJsonPath('invitation.motion_preset', 'cinematic')
            ->assertJsonPath('invitation.decorative_effects', false);

        $this->patchJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}", [
            'motion_preset' => 'unexpected',
        ])->assertUnprocessable();

        $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/publish", ['published' => true])->assertOk();
        $this->getJson("/api/public/invitations/{$invitation['slug']}")->assertOk()
            ->assertJsonPath('invitation.motion_preset', 'cinematic')
            ->assertJsonPath('invitation.decorative_effects', false);
    }

    public function test_invitation_cover_photo_is_stored_served_and_removable(): void
    {
        Storage::fake('local');
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $invitation = $this->postJson("/api/workspaces/{$workspace->id}/invitation", [
            'title' => 'Undangan Ayu & Bima', 'theme_key' => 'botanical',
        ])->assertCreated()->json('invitation');

        $upload = $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/cover", [
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 1600),
        ])->assertOk()->json('invitation');
        $this->assertStringStartsWith("invitations/{$workspace->id}/", $upload['cover_image_path']);
        Storage::disk('local')->assertExists($upload['cover_image_path']);

        $other = User::create(['name' => 'Rani', 'email' => uniqid('rani').'@example.test', 'password' => 'Wedding2027']);
        $this->actingAs($other)->get("/api/public/invitations/{$invitation['slug']}/cover")->assertForbidden();
        $this->actingAs($user);
        $privateCover = $this->get("/api/public/invitations/{$invitation['slug']}/cover")->assertOk();
        $this->assertStringContainsString('private', $privateCover->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', $privateCover->headers->get('cache-control'));

        $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/publish", ['published' => true])->assertOk();
        $publicData = $this->getJson("/api/public/invitations/{$invitation['slug']}")->assertOk()->json('invitation');
        $this->assertStringStartsWith('/api/public/invitations/'.$invitation['slug'].'/cover?v=', $publicData['cover_image_url']);
        $this->get("/api/public/invitations/{$invitation['slug']}/cover")->assertOk()->assertHeader('content-type', 'image/jpeg');

        $this->deleteJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/cover")
            ->assertOk()->assertJsonPath('invitation.cover_image_url', null);
        Storage::disk('local')->assertMissing($upload['cover_image_path']);
    }

    public function test_invitation_gallery_is_private_until_publish_and_can_be_managed(): void
    {
        Storage::fake('local');
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $invitation = $this->postJson("/api/workspaces/{$workspace->id}/invitation", [
            'title' => 'Undangan Ayu & Bima', 'theme_key' => 'botanical',
        ])->assertCreated()->json('invitation');

        $images = $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/gallery", [
            'images' => [UploadedFile::fake()->image('cerita-1.jpg', 1200, 1600), UploadedFile::fake()->image('cerita-2.jpg', 1200, 1600)],
        ])->assertCreated()->assertJsonCount(2, 'images')->json('images');
        Storage::disk('local')->assertExists($images[0]['image_path']);

        $this->app['auth']->logout();
        $this->getJson("/api/public/invitations/{$invitation['slug']}")->assertNotFound();
        $this->get("/api/public/invitations/{$invitation['slug']}/gallery/{$images[0]['id']}")->assertNotFound();
        $this->actingAs($user);
        $this->postJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/publish", ['published' => true])->assertOk();
        $publicImages = $this->getJson("/api/public/invitations/{$invitation['slug']}")->assertOk()->json('invitation.gallery_images');
        $this->assertCount(2, $publicImages);
        $this->get($publicImages[0]['url'])->assertOk()->assertHeader('content-type', 'image/jpeg');

        $this->deleteJson("/api/workspaces/{$workspace->id}/invitation/{$invitation['id']}/gallery/{$images[0]['id']}")
            ->assertOk()->assertJsonCount(1, 'images');
        Storage::disk('local')->assertMissing($images[0]['image_path']);
    }

    public function test_workspace_settings_are_owner_only_and_profile_can_be_updated(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);
        $this->patchJson("/api/workspaces/{$workspace->id}/settings", ['name' => 'Ayu & Bima', 'partner_one_name' => 'Ayu', 'partner_two_name' => 'Bima', 'wedding_date' => '2027-05-12', 'timezone' => 'Asia/Jakarta'])
            ->assertOk()->assertJsonPath('workspace.partner_one_name', 'Ayu');
        $this->patchJson('/api/profile', ['name' => 'Ayu Santosa', 'email' => 'ayu@example.test'])->assertOk()->assertJsonPath('user.name', 'Ayu Santosa');
        $other = User::create(['name' => 'Rani', 'email' => 'rani@example.test', 'password' => 'Wedding2027']);
        $this->actingAs($other)->patchJson("/api/workspaces/{$workspace->id}/settings", ['name' => 'Tidak berhak', 'timezone' => 'UTC'])->assertForbidden();
    }

    private function workspace(): array
    {
        $user = User::create(['name' => 'Ayu', 'email' => uniqid('ayu').'@example.test', 'password' => 'Wedding2027']);
        $workspace = $user->ownedWorkspaces()->create(['name' => 'Ayu & Bima']);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);
        return [$user, $workspace];
    }

    private function workspaceFor(User $user, string $name): Workspace
    {
        $workspace = $user->ownedWorkspaces()->create(['name' => $name]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);
        return $workspace;
    }
}
