<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\InvitePartnerRequest;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()->workspaces()
            ->wherePivot('status', 'active')
            ->select('workspaces.id', 'workspaces.name', 'workspaces.partner_one_name', 'workspaces.partner_two_name', 'workspaces.wedding_date', 'workspaces.timezone', 'workspaces.onboarding_completed_at')
            ->get()
            ->map(fn (Workspace $workspace) => $this->workspacePayload($workspace));

        return response()->json(['workspaces' => $workspaces]);
    }

    public function members(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $members = $workspace->members()
            ->wherePivot('status', 'active')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->pivot->role,
            ]);

        return response()->json(['members' => $members]);
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = DB::transaction(function () use ($request): Workspace {
            $workspace = $request->user()->ownedWorkspaces()->create($request->validated());
            $workspace->members()->attach($request->user()->id, ['role' => 'owner', 'status' => 'active']);

            return $workspace;
        });

        return response()->json(['workspace' => $this->workspacePayload($workspace)], 201);
    }

    public function complete(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);
        $workspace->forceFill(['onboarding_completed_at' => now()])->save();

        return response()->json(['workspace' => $this->workspacePayload($workspace->refresh())]);
    }

    public function invite(InvitePartnerRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('invite', $workspace);

        if (Str::lower($request->string('email')->toString()) === Str::lower($request->user()->email)) {
            throw ValidationException::withMessages(['email' => 'Gunakan alamat email partner, bukan email akunmu.']);
        }

        if ($workspace->members()->where('users.email', $request->string('email')->toString())->wherePivot('status', 'active')->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah menjadi anggota workspace.']);
        }

        if ($workspace->members()->wherePivot('role', 'partner')->wherePivot('status', 'active')->exists()) {
            throw ValidationException::withMessages(['email' => 'Workspace ini sudah memiliki partner aktif.']);
        }

        $pendingInvitation = $workspace->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
        if ($pendingInvitation && Str::lower($pendingInvitation->email) !== Str::lower($request->string('email')->toString())) {
            throw ValidationException::withMessages(['email' => 'Selesaikan atau biarkan kedaluwarsa undangan yang sedang menunggu.']);
        }

        $token = Str::random(64);
        $workspace->invitations()
            ->where('email', $request->string('email')->toString())
            ->whereNull('accepted_at')
            ->delete();
        $invitation = $workspace->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => $request->string('email')->toString(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($workspace, $token));

        return response()->json([
            'invitation' => ['email' => $invitation->email, 'expires_at' => $invitation->expires_at->toIso8601String()],
            'message' => 'Undangan partner sudah dikirim.',
        ], 201);
    }

    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation || Str::lower($invitation->email) !== Str::lower($request->user()->email)) {
            throw ValidationException::withMessages(['invitation' => 'Undangan tidak valid, kedaluwarsa, atau bukan untuk akun ini.']);
        }

        DB::transaction(function () use ($invitation, $request): void {
            $invitation->workspace->members()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'partner', 'status' => 'active'],
            ]);
            $invitation->forceFill(['accepted_at' => now()])->save();
        });

        return response()->json(['message' => 'Kamu sudah bergabung ke workspace pasangan.']);
    }

    public function invitation(Request $request, string $token): JsonResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->with('workspace:id,name')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        [$local, $domain] = explode('@', $invitation->email, 2);
        $maskedEmail = mb_substr($local, 0, 1).str_repeat('•', max(mb_strlen($local) - 1, 2)).'@'.$domain;

        return response()->json([
            'workspace_name' => $invitation->workspace->name,
            'email' => $maskedEmail,
            'email_matches' => $request->user()
                ? Str::lower($request->user()->email) === Str::lower($invitation->email)
                : null,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ]);
    }

    private function workspacePayload(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'partner_one_name' => $workspace->partner_one_name,
            'partner_two_name' => $workspace->partner_two_name,
            'wedding_date' => $workspace->wedding_date?->toDateString(),
            'timezone' => $workspace->timezone,
            'onboarding_completed_at' => $workspace->onboarding_completed_at?->toIso8601String(),
        ];
    }
}
